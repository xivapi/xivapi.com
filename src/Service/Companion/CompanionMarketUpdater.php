<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionToken;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use Companion\CompanionApi;
use Companion\Config\CompanionConfig;
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
    const MAX_PER_CRONJOB       = 100;
    const MAX_PER_CHUNK         = 11; // Does around 88 items
    const MAX_CRONJOB_DURATION  = 55;
    const MAX_QUERY_SLEEP       = 3; // in seconds
    
    // deprecated - used in companion priority math
    const MAX_QUERY_DURATION = 3;
    
    
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
    /** @var array */
    private $tokens;
    /** @var int */
    private $start;
    
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
        $this->start = time();
    }
    
    public function update(int $priority, int $queue)
    {
        // random sleep at start, this is so not all queries against sight start at the same time.
        usleep( mt_rand(10, 1500) * 1000 );

        // grab our companion tokens
        $this->tokens = $this->companionTokenManager->getCompanionTokensPerServer();
        
        /** @var CompanionMarketItemEntry[] $entries */
        $items = $this->repository->findItemsToUpdate(
            $priority,
            self::MAX_PER_CRONJOB,
            self::MAX_PER_CRONJOB * $queue
        );
        
        // no items???
        if (empty($items)) {
            $this->console->writeln('ERROR: No items to update!? Da fook!');
            return;
        }

        // loop through chunks
        $this->console->writeln("Start: ". date('Y-m-d H:i:s'));
        $a = microtime(true);
        
        foreach (array_chunk($items, self::MAX_PER_CHUNK) as $i => $itemChunk) {
            // if we're close to the cronjob minute mark, end
            if ((time() - $this->start) > self::MAX_CRONJOB_DURATION) {
                $this->console->writeln('Ending auto-update as time limit seconds reached.');
                return;
            }
            
            // handle the chunk
            $this->updateChunk($i, $itemChunk);
        }
    
        # --------------------------------------------------------------------------------------------------------------
        $duration = round(microtime(true) - $a, 2);
        $reqSec = round(1 / round($duration / (self::MAX_PER_CHUNK * 2), 2), 1);
        $this->console->writeln("Finish: ". date('Y-m-d H:i:s') ." - duration = {$duration} @ req/sec: {$reqSec}");
        # --------------------------------------------------------------------------------------------------------------
    
        $this->em->clear();
    }
    
    /**
     * Update a group of items
     */
    private function updateChunk($chunkNumber, $chunkList)
    {
        $this->console->writeln("Processing chunk: {$chunkNumber}");
        $start = microtime(true);
        
        // initialize Companion API, no token provided as we set it later on
        // also enable async
        $api = new CompanionApi();
        $api->useAsync();
        
        /** @var CompanionMarketItemEntry $item */
        $requests = [];
        foreach ($chunkList as $item) {
            $itemId = $item->getItem();
            $server = GameServers::LIST[$item->getServer()];
            
            /** @var CompanionToken $token */
            $token  = $this->tokens[$server];
            
            if ($api->Token()->hasExpired($token->getToken())) {
                $this->console->writeln("!!! Error: Token has expired for server: {$server}. Run: Companion_AutoLoginAccountsCommand");
                $this->console->writeln("!!! Error: Script forcefully exiting.");
                exit;
            }

            // set the Sight token for these requests (required so it switches server)
            $api->Token()->set($token->getToken());
            
            // add requests
            $requests["{$itemId}_{$server}_prices"]  = $api->Market()->getItemMarketListings($itemId);
            $requests["{$itemId}_{$server}_history"] = $api->Market()->getTransactionHistory($itemId);
        }
        
        // run the requests, we don't care on response because the first time nothing will be there.
        $this->console->writeln("<info>Part 1: Sending Requests</info>");
        $a = microtime(true);

        $api->Sight()->settle($requests)->wait();
    
        # --------------------------------------------------------------------------------------------------------------
        $duration = round(microtime(true) - $a, 2);
        $reqSec = round(1 / round($duration / (self::MAX_PER_CHUNK * 2), 2), 1);
        $this->console->writeln("--| duration = {$duration} @ req/sec: {$reqSec}");
        # --------------------------------------------------------------------------------------------------------------
        
        // we only wait if the execution of the above requests was faster than our default timeout
        $sleep = ceil(self::MAX_QUERY_SLEEP - $duration);
        if ($sleep > 1) {
            $this->console->writeln("--| wait: {$sleep}");
            sleep($sleep);
        }
        
        // run the requests again, the Sight API should give us our response this time.
        $this->console->writeln("<info>Part 2: Fetching Responses</info>");
        $a = microtime(true);

        $results = $api->Sight()->settle($requests)->wait();

        # --------------------------------------------------------------------------------------------------------------
        $duration = round(microtime(true) - $a, 2);
        $reqSec = round(1 / round($duration / (self::MAX_PER_CHUNK * 2), 2), 1);
        $this->console->writeln("--| duration = {$duration} @ req/sec: {$reqSec}");
        # --------------------------------------------------------------------------------------------------------------
        
        // handle the results of the response
        $results = $api->Sight()->handle($results);
    
        # --------------------------------------------------------------------------------------------------------------
        $duration = round(microtime(true) - $start, 2);
        $this->console->writeln("--| final duration = {$duration}");
        # --------------------------------------------------------------------------------------------------------------
        
        $this->updateItems($chunkList, $results);
    }
    
    /**
     * Update a chunk of items to the document storage
     */
    private function updateItems($chunkList, $results)
    {
        // process the chunk list from our results
        /** @var CompanionMarketItemEntry $item */
        foreach ($chunkList as $item) {
            $itemId = $item->getItem();
            $server = GameServers::LIST[$item->getServer()];
        
            // grab our prices and history
            /** @var \stdClass $prices */
            /** @var \stdClass $history */
            $prices  = $results->{"{$itemId}_{$server}_prices"} ?? null;
            $history = $results->{"{$itemId}_{$server}_history"} ?? null;
        
            // grab market item document
            $marketItem = $this->getMarketItemDocument($item);
        
            // ------------------------------
            // CURRENT PRICES
            // ------------------------------
            if ($prices && isset($prices->error) === false && $prices->entries) {
                // reset prices
                $marketItem->Prices = [];
            
                // append current prices
                foreach ($prices->entries as $row) {
                    $marketItem->Prices[] = MarketListing::build($row);
                }
            }
        
            // ------------------------------
            // CURRENT HISTORY
            // ------------------------------
            if ($history && isset($prices->error) === false && $history->history) {
                foreach ($history->history as $row) {
                    // build a custom ID based on a few factors (History can't change)
                    // we don't include character name as I'm unsure if it changes if you rename yourself
                    $id = sha1(vsprintf("%s_%s_%s_%s_%s", [
                        $itemId,
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
            $item->setUpdated(time())->incUpdates();
            $this->em->persist($item);
            $this->em->flush();
        
            $this->console->writeln("<comment>✓</comment> Updated prices + history for item: {$itemId} on {$server}");
        }
    }
    
    /**
     * Get the elastic search document
     */
    private function getMarketItemDocument(CompanionMarketItemEntry $entry): MarketItem
    {
        $marketItem = $this->companionMarket->get($entry->getServer(), $entry->getItem());
        $marketItem = $marketItem ?: new MarketItem($entry->getServer(), $entry->getItem());
        return $marketItem;
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
