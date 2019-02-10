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
    
    public function __construct(EntityManagerInterface $em, Companion $companion, CompanionMarket $companionMarket)
    {
        $this->em               = $em;
        $this->companion        = $companion;
        $this->companionMarket  = $companionMarket;
        $this->repository       = $this->em->getRepository(CompanionMarketItemEntry::class);
        $this->console          = new ConsoleOutput();
    }
    
    public function process(int $priority, int $offset, int $limit)
    {
        $start = time();
        
        $serverId = GameServers::getServerId('Phoenix');
        
        /** @var CompanionMarketItemEntry[] $entries */
        $entries = $this->repository->findBy(
            [ 'priority' => $priority, 'server' => $serverId ], [ 'updated' => 'asc' ], $limit, $offset
        );
        
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
            if ((time() - $start) > 55) {
                $this->console->writeln('Ending auto-update as 55 seconds reached.');
                return;
            }
    
            $time = date('H:i:s');
            $serverName = GameServers::LIST[$serverId];
            $section->overwrite("> [{$time}] [{$entry->getPriority()}] Server: {$entry->getServer()} {$serverName} - ItemID: {$entry->getItem()} ");
    
            // try get existing item, otherwise create a new one
            $marketItem = $this->companionMarket->get($entry->getServer(), $entry->getItem());
            $marketItem = $marketItem ?: new MarketItem($entry->getServer(), $entry->getItem());

            // grab prices + history from sight api
            $sightData = $this->getCompanionMarketData($entry->getServer(), $entry->getItem());
            
            if ($sightData === null) {
                $this->console->writeln("No market data for: {$entry->getItem()} on server: {$entry->getServer()}");
                continue;
            }
            
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
    private function getCompanionMarketData($serverId, $itemId)
    {
        $serverName = GameServers::LIST[$serverId];
    
        try {
            $prices  = $this->companion->getItemPrices($serverName, $itemId);
            $history = $this->companion->getItemHistory($serverName, $itemId);
            
            return [ $prices, $history ];
        } catch (\Exception $ex) {
            // record failed attempts
            $marketItemException = new CompanionMarketItemException();
            $marketItemException
                ->setItem($itemId)
                ->setServer($serverId)
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
