<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionCharacter;
use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionRetainer;
use App\Entity\CompanionToken;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionErrorHandler;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\ThirdParty\GoogleAnalytics;
use Companion\CompanionApi;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Auto-Update item price + history
 */
class MarketUpdater
{
    const PRICES  = 10;
    const HISTORY = 50;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionCharacterRepository */
    private $repositoryCompanionCharacter;
    /** @var CompanionRetainerRepository */
    private $repositoryCompanionRetainer;

    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarket */
    private $market;
    /** @var CompanionErrorHandler */
    private $errorHandler;
    /** @var array */
    private $tokens = [];
    /** @var array */
    private $items = [];
    /** @var array */
    private $marketItemEntryUpdated = [];
    /** @var int */
    private $priority = 0;
    /** @var int */
    private $queue = 0;
    /** @var int */
    private $deadline = 0;
    /** @var array */
    private $requestIds = [];
    /** @var array */
    private $requestRejections = [];
    /** @var CompanionApi */
    private $api;
    /** @var array */
    private $startTime;

    public function __construct(
        EntityManagerInterface $em,
        CompanionMarket $companionMarket,
        CompanionErrorHandler $companionErrorHandler
    ) {
        $this->em           = $em;
        $this->market       = $companionMarket;
        $this->errorHandler = $companionErrorHandler;
        $this->console      = new ConsoleOutput();

        // repositories for market data
        $this->repositoryCompanionCharacter = $this->em->getRepository(CompanionCharacter::class);
        $this->repositoryCompanionRetainer  = $this->em->getRepository(CompanionRetainer::class);
    
        // initialize Companion API
        $this->api = new CompanionApi();
        $this->api->useAsync();
    }

    /**
     * Update a series of items in a queue.
     */
    public function update(int $priority, int $queue, int $patreonQueue = null)
    {
        $this->console("Priority: {$priority} - Queue: {$queue}");
        $this->startTime = microtime(true);
        $this->deadline = time() + CompanionConfiguration::CRONJOB_TIMEOUT_SECONDS;
        $this->priority = $priority;
        $this->queue = $queue;
        $this->console('Starting!');
        
        // Build 100 (50 for prices, 50 for history
        foreach (range(0, 100) as $i) {
            $this->requestIds[$i] = Uuid::uuid4()->toString();
        }
        
        //--------------------------------------------------------------------------------------------------------------

        // check error status
        $this->checkErrorState();

        // fetch companion tokens
        $this->fetchCompanionTokens();

        // fetch item ids to update
        $this->fetchItemIdsToUpdate($priority, $queue, $patreonQueue);
        
        if (empty($this->items)) {
            $this->console('No items to update');
            $this->closeDatabaseConnection();
            return;
        }

        // check things didn't take too long to start
        $this->checkDeadline();

        // 1st pass - Send Requests
        foreach ($this->items as $i => $item) {
            $this->performRequests($i, $item, 'SEND REQUESTS');
        }
    
        $this->checkErrorState();
    
        // check things didn't take too long to start
        $this->checkDeadline();

        // sleep
        $this->console("--- Waiting ---");
        sleep(
            mt_rand(
                CompanionConfiguration::DELAY_BETWEEN_REQUEST_RESPONSE[0],
                CompanionConfiguration::DELAY_BETWEEN_REQUEST_RESPONSE[1]
            )
        );

        // 2nd pass - Fetch Responses
        foreach ($this->items as $i => $item) {
            [$prices, $history] = $this->performRequests($i, $item, 'FETCH RESPONSES');
            $this->storeMarketData($item, $prices, $history);
        }

        // update the database market entries with the latest updated timestamps
        $this->updateDatabaseMarketItemEntries();
        $this->em->flush();

        // finish, output completed duration
        $duration = round(microtime(true) - $this->startTime, 1);
        $this->console("-> Completed. Duration: <comment>{$duration}</comment>");
        $this->closeDatabaseConnection();
    }
    
    /**
     * Perform market requests
     */
    private function performRequests($i, $item, $stage)
    {
        $i = $i + 1;
        
        $this->checkErrorState();
    
        $itemId     = $item['item'];
        $server     = $item['server'];
        $serverName = GameServers::LIST[$server];
        $serverDc   = GameServers::getDataCenter($serverName);
    
        /** @var CompanionToken $token */
        $token = $this->tokens[$server];
    
        if ($token == null) {
            $this->console("Token has expired for server: ({$server}) {$serverName} - {$serverDc}, skipping...");
            return [null,null];
        }
    
        // Set server token
        $this->api->Token()->set($token);
    
        // Setup market requests for Price + History
        $requests = [
            $this->requestIds[$i + self::PRICES]  => $this->api->Market()->getItemMarketListings($itemId),
            $this->requestIds[$i + self::HISTORY] => $this->api->Market()->getTransactionHistory($itemId),
        ];
    
        try {
            // Send async requests
            $results = $this->api->Sight()->settle($requests)->wait();
            $results = $this->api->Sight()->handle($results);
        
            // Get the response for the Prices + History
            $prices  = $results->{$this->requestIds[$i + self::PRICES]} ?? null;
            $history = $results->{$this->requestIds[$i + self::HISTORY]} ?? null;
        
            // check if we were rejected
            $this->checkRequestForRejection($item, $prices, $history);
        } catch (\Exception $ex) {
            $this->console("({$i}) - Exception thrown for: {$itemId} on: {$server} {$serverName} - {$serverDc}");
            return [null,null];
        }
    
        // Record to Google Analytics
        GoogleAnalytics::companionTrackItemAsUrl("/{$itemId}/Prices");
        GoogleAnalytics::companionTrackItemAsUrl("/{$itemId}/History");
    
        // log
        $this->console("({$i} {$stage} :: {$itemId} on {$server} {$serverName} - {$serverDc}");
    
        // slow down req/sec
        usleep(
            mt_rand(
                CompanionConfiguration::DELAY_BETWEEN_REQUESTS_MS[0],
                CompanionConfiguration::DELAY_BETWEEN_REQUESTS_MS[1]
            ) * 1000
        );
        
        return [$prices, $history];
    }
    
    /**
     * Checks for any req/sec issues
     */
    private function checkRequestForRejection(array $item, $prices, $history)
    {
        $itemId     = $item['item'];
        $server     = $item['server'];
        $serverName = GameServers::LIST[$server];
        $serverDc   = GameServers::getDataCenter($serverName);
        
        if (isset($prices->state) && $prices->state == "rejected" || isset($history->state) && $history->state == "rejected") {
            $this->errorHandler->exception("Rejected", "Error: {$itemId} : ({$server}) {$serverName} - {$serverDc}");
            $this->requestRejections[$itemId] = true;
        }
    }
    
    /**
     * Check for errors
     */
    private function checkErrorState()
    {
        if ($this->errorHandler->getCriticalExceptionCount() > CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            $this->console('Exceptions are above the ERROR_COUNT_THRESHOLD.');
            $this->closeDatabaseConnection();
            exit();
        }
    }
    
    /**
     * Tests to see if the time deadline has hit
     */
    private function checkDeadline()
    {
        // if we go over the deadline, we stop.
        if (time() > $this->deadline) {
            $this->console(date('H:i:s') ." | Ending auto-update as time limit seconds reached.");
            $this->closeDatabaseConnection();
            exit();
        }
    }

    /**
     * Store the market data
    *
     * @param array $item
     * @param \stdClass $prices
     * @param \stdClass $history
     */
    private function storeMarketData($item, $prices, $history)
    {
        $itemId     = $item['item'];
        $server     = $item['server'];
        $serverName = GameServers::LIST[$server];
        $serverDc   = GameServers::getDataCenter($serverName);
    
        /**
         * CHECK SHIT DIDNT BREAK --------------------------------------------------------------------------------------
         */

        // check for errors
        if (isset($prices->error) || isset($history->error)) {
            $this->errorHandler->exception($prices->reason, "RESPONSE ERROR: {$itemId} : ({$server}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_error');
            $this->console("! Error Response");
            return;
        }

        // check for rejections
        if (isset($prices->state) && $prices->state == "rejected" || isset($history->state) && $history->state == "rejected") {
            $this->errorHandler->exception("Rejected", "REJECTED: {$itemId} : ({$server}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_rejected');
            $this->console("! Rejected Response");
            return;
        }

        // if responses are null
        if ($prices === null && $history == null) {
            $this->errorHandler->exception('Empty Response', "DATA EMPTY: {$itemId} : ({$server}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_empty');
            $this->console("! Empty Response");
            return;
        }
    
        // update item entry
        $this->marketItemEntryUpdated[] = $itemId;
    
        /**
         * SAVE --------------------------------------------------------------------------------------------------------
         */
    
        // grab market item document
        $marketItem = $this->getMarketItemDocument($server, $itemId);
    
        // record lodestone info
        $marketItem->LodestoneID = $prices->eorzeadbItemId;
        $this->console("Lodestone ID: = {$prices->eorzeadbItemId}");

        // CURRENT PRICES
        if ($prices && isset($prices->error) === false && $prices->entries) {
            // reset prices
            $marketItem->Prices = [];

            // append current prices
            foreach ($prices->entries as $row) {
                // try build a semi unique id
                $id = sha1(
                    implode("_", [
                        $itemId,
                        $row->isCrafted,
                        $row->hq,
                        $row->sellPrice,
                        $row->stack,
                        $row->registerTown,
                        $row->sellRetainerName,
                    ])
                );

                // grab internal records
                $row->_retainerId = $this->getInternalRetainerId($server, $row->sellRetainerName);
                $row->_creatorSignatureId = $this->getInternalCharacterId($server, $row->signatureName);

                // append prices
                $marketItem->Prices[] = MarketListing::build($id, $row);
            }

            // sort prices low -> high
            usort($marketItem->Prices, function($first,$second) {
                return $first->PricePerUnit > $second->PricePerUnit;
            });
        }

        // CURRENT HISTORY
        if ($history && isset($history->error) === false && $history->history) {
            foreach ($history->history as $row) {
                // build a custom ID based on a few factors (History can't change)
                // we don't include character name as I'm unsure if it changes if you rename yourself
                $id = sha1(
                    implode("_", [
                        $itemId,
                        $row->stack,
                        $row->hq,
                        $row->sellPrice,
                        $row->buyRealDate,
                    ])
                );

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

                // grab internal record
                $row->_characterId = $this->getInternalCharacterId($server, $row->buyCharacterName);

                // add history to front
                array_unshift($marketItem->History, MarketHistory::build($id, $row));
            }

            // sort history new -> old
            usort($marketItem->History, function($first,$second) {
                return $first->PurchaseDate < $second->PurchaseDate;
            });
        }
        
        // save market item
        $this->market->set($marketItem);
    }
    
    /**
     * Returns the ID for internally stored retainers
     */
    private function getInternalRetainerId(int $server, string $name): ?string
    {
        return $this->handleMarketTrackingNames(
            $server,
            $name,
            $this->repositoryCompanionRetainer,
            CompanionRetainer::class
        );
    }
    
    /**
     * Returns the ID for internally stored character ids
     */
    private function getInternalCharacterId(int $server, string $name): ?string
    {
        return $this->handleMarketTrackingNames(
            $server,
            $name,
            $this->repositoryCompanionCharacter,
            CompanionCharacter::class
        );
    }
    
    /**
     * Handles the tracking logic for all name fields
     */
    private function handleMarketTrackingNames(int $server, string $name, ObjectRepository $repository, $class)
    {
        if (empty($name)) {
            return null;
        }
        
        $obj = $repository->findOneBy([
            'name'   => $name,
            'server' => $server,
        ]);
        
        if ($obj === null) {
            $obj = new $class($name, $server);
            $this->em->persist($obj);
            $this->em->flush();
        }
        
        return $obj->getId();
    }

    /**
     * Get the elastic search document
     */
    private function getMarketItemDocument($server, $itemId): MarketItem
    {
        // return an existing one, otherwise return a new one
        return $this->market->get($server, $itemId, null, true);
    }

    /**
     * Fetches items to auto-update, this is performed here as the entity
     * manager is quite slow for thousands of throughput every second.
     */
    private function fetchItemIdsToUpdate($priority, $queue, $patreonQueue)
    {
        // get items to update
        $this->console('Finding Item IDs to Auto-Update');
        $s = microtime(true);

        // patreon get their own table.
        $limit = CompanionConfiguration::MAX_ITEMS_PER_CRONJOB;
        $where = $patreonQueue ? "patreon_queue = {$patreonQueue}" : "priority = {$priority} AND consumer = ${queue}";

        $sql = "
            SELECT id, item, server
            FROM companion_market_item_queue
            WHERE {$where}
            LIMIT {$limit}
        ";
        
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();

        $this->items = $stmt->fetchAll();
        
        $sqlDuration = round(microtime(true) - $s, 2);
        $this->console("Obtained items in: {$sqlDuration} seconds");
    }

    /**
     * Fetch the companion tokens.
     */
    private function fetchCompanionTokens()
    {
        $conn = $this->em->getConnection();
        $sql  = "SELECT server, online, token FROM companion_tokens";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        foreach ($stmt->fetchAll() as $arr) {
            $serverId = GameServers::getServerId($arr['server']);
            $token = json_decode($arr['token']);
    
            $this->tokens[$serverId] = $arr['online'] ? $token : null;
        }
    }

    /**
     * Update item entry
     */
    private function updateDatabaseMarketItemEntries()
    {
        $this->console('Updating database item entries');
        $conn = $this->em->getConnection();

        foreach ($this->marketItemEntryUpdated as $id) {
            $sql = "UPDATE companion_market_item_entry SET updated = ". time() .", patreon_queue = NULL WHERE id = '{$id}'";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }
    }

    /**
     * Write to log
     */
    private function console($text)
    {
        $this->console->writeln(date('Y-m-d H:i:s') . " | {$this->priority} | {$this->queue} | {$text}");
    }
    
    /**
     * Close the db connections
     */
    private function closeDatabaseConnection()
    {
        $this->em->flush();
        $this->em->clear();
        $this->em->close();
        $this->em->getConnection()->close();
    }
    
    /**
     * Get a single market item entry.
     */
    public function getMarketItemEntry(int $serverId, int $itemId)
    {
        return $this->em->getRepository(CompanionMarketItemEntry::class)->findOneBy([
            'server' => $serverId,
            'item'   => $itemId,
        ]);
    }
    
    /**
     * Mark an item to be manually updated on an DC
     */
    public function updateManual(int $itemId, int $server, int $queueNumber)
    {
        /** @var CompanionMarketItemEntryRepository $repo */
        $repo    = $this->em->getRepository(CompanionMarketItemEntry::class);
        $servers = GameServers::getDataCenterServersIds(GameServers::LIST[$server]);
        $items   = $repo->findItemsInServers($itemId, $servers);
        
        /** @var CompanionMarketItemEntry $item */
        foreach ($items as $item) {
            $item->setPatreonQueue($queueNumber);
            $this->em->persist($item);
        }
        
        $this->em->flush();
    }
}
