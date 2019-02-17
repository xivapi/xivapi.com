<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Auto-Update item price + history
 * 1 item takes 3 seconds
 * 1 cronjob can do ~20 items.
 * - around 30,750 CronJobs to handle
 */
class CompanionMarketUpdater
{
    const MAX_PER_CRONJOB = 18;
    const MAX_QUERY_DURATION = 3;
    const MAX_CRONJOB_DURATION = 55;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarketItemEntryRepository */
    private $repository;
    /** @var Companion */
    private $companion;
    /** @var CompanionMarket */
    private $companionMarket;
    /** @var CompanionMarket */
    private $companionTokenManager;
    
    public function __construct(
        EntityManagerInterface $em,
        Companion $companion,
        CompanionMarket $companionMarket,
        CompanionTokenManager $companionTokenManager
    ) {
        $this->em = $em;
        $this->companion = $companion;
        $this->companionMarket = $companionMarket;
        $this->companionTokenManager = $companionTokenManager;
        $this->repository = $this->em->getRepository(CompanionMarketItemEntry::class);
        $this->console = new ConsoleOutput();
    }
    
    public function process(int $priority, int $queue)
    {
        $start  = time();
        $limit  = self::MAX_PER_CRONJOB;
        $offset = self::MAX_PER_CRONJOB * $queue;
        $tokens = $this->companionTokenManager->getCompanionTokensPerServer();
        
        /** @var CompanionMarketItemEntry[] $entries */
        $entries = $this->repository->findItemsToUpdate($priority, $limit, $offset);
        
        // no items???
        if (empty($entries)) {
            $this->console->writeln('ERROR: No items to update!? Da fook!');
            return;
        }
    
        $total = count($entries);
        $this->console->writeln("Total Items: {$total}");
        $this->console->writeln('Updating Prices + History');
        $section = $this->console->section();
        
        /** @var CompanionMarketItemEntry $item */
        foreach ($entries as $entry) {
            // if we're close to the cronjob minute mark, end
            if ((time() - $start) > self::MAX_CRONJOB_DURATION) {
                $this->console->writeln('Ending auto-update as time limit seconds reached.');
                return;
            }

            // start
            $time = date('H:i:s');
            $serverName = GameServers::LIST[$entry->getServer()];
            $section->overwrite("> [{$time}] [{$entry->getPriority()}] Server: {$entry->getServer()} {$serverName} - ItemID: {$entry->getItem()} ");
    
            // set the companion API token
            $token = $tokens[$serverName];
            $this->companion->setCompanionApiToken($token->getToken());
            
            // try get existing item, otherwise create a new one
            $marketItem = $this->companionMarket->get($entry->getServer(), $entry->getItem());
            $marketItem = $marketItem ?: new MarketItem($entry->getServer(), $entry->getItem());

            // grab prices + history from sight api
            $sightData = $this->getCompanionMarketData($entry->getItem());
            
            if ($sightData === null) {
                file_put_contents(__DIR__.'/CompanionMarketUpdater_Error.txt', time() . PHP_EOL, FILE_APPEND);
                $this->console->writeln("No market data for: {$entry->getItem()} on server: {$entry->getServer()}");
                continue;
            }
    
            file_put_contents(__DIR__.'/CompanionMarketUpdater_Success.txt', time() . PHP_EOL, FILE_APPEND);
    
            [ $prices, $history ] = $sightData;
    
            //
            // convert prices
            //

            // reset prices
            $marketItem->Prices = [];
    
            // append current prices
            foreach ($prices->entries as $row) {
                $marketItem->Prices[] = MarketListing::build($row);
            }
    
            //
            // convert historic data
            //
            if ($history->history) {
                foreach ($history->history as $row) {
                    // build a custom ID based on a few factors (History can't change)
                    // we don't include character name as I'm unsure if it changes if you rename yourself
                    $id = sha1(vsprintf("%s_%s_%s_%s_%s", [
                        $entry->getItem(),
                        $row->stack,
                        $row->hq,
                        $row->sellPrice,
                        $row->buyRealDate,
                    ]));
            
                    // if this entry is in our history, then just finish
                    $found = false;
                    foreach ($marketItem->History as $existing) {
                        if ($existing->ID == $id) {
                            $found = true;
                            break;
                        }
                    }
            
                    // once we've found an existing entry we don't need to add anymore
                    if ($found) {
                        break;
                    }
            
                    // add history to front
                    array_unshift($marketItem->History, MarketHistory::build($id, $row));
                }
            }
    
            // put
            $this->companionMarket->set($marketItem);
            file_put_contents(__DIR__.'/CompanionMarket.json', "{$marketItem->ItemID} {$marketItem->Server} \n", FILE_APPEND);
            
            // update entry
            $entry->setUpdated(time());
            
            $this->em->persist($entry);
            $this->em->flush();
        }
    
        $this->em->clear();

        $this->console->writeln('Finished!');
    }
    
    /**
     * Returns the Prices + History for an item on a specific server, or returns null
     */
    private function getCompanionMarketData($itemId)
    {
        try {
            $prices  = $this->companion->getItemPrices($itemId);
            $history = $this->companion->getItemHistory($itemId);
            
            return [ $prices, $history ];
        } catch (\Exception $ex) {
            // record failed attempts
            $marketItemException = new CompanionMarketItemException();
            $marketItemException
                ->setException(get_class($ex))
                ->setMessage($ex->getMessage());
        
            $this->em->persist($marketItemException);
            $this->em->flush();
        }

        return null;
    }
    
    private function one(int $serverId, int $itemId)
    {
        // todo - implement logic for updating 1 item on 1 server
    }
}
