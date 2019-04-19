<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionCharacter;
use App\Entity\CompanionError;
use App\Entity\CompanionMarketItemUpdate;
use App\Entity\CompanionRetainer;
use App\Entity\CompanionToken;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionErrorHandler;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\ThirdParty\Discord\Discord;
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
    /** @var array  */
    private $requests = [];
    /** @var int */
    private $priority = 0;
    /** @var int */
    private $queue = 0;
    /** @var int */
    private $exceptions = 0;
    /** @var array */
    private $times = [
        'startTime'  => 0,
        'firstPass'  => 0,
        'secondPass' => 0,
    ];


    public function __construct(
        EntityManagerInterface $em,
        CompanionMarket $companionMarket,
        CompanionErrorHandler $companionErrorHandler
    ) {
        $this->em           = $em;
        $this->market       = $companionMarket;
        $this->errorHandler = $companionErrorHandler;
        $this->console      = new ConsoleOutput();
        $this->times        = (Object)$this->times;

        // repositories for market data
        $this->repositoryCompanionCharacter = $this->em->getRepository(CompanionCharacter::class);
        $this->repositoryCompanionRetainer  = $this->em->getRepository(CompanionRetainer::class);
    }

    /**
     * Update a series of items in a queue.
     */
    public function update(int $priority, int $queue, int $patreonQueue = null)
    {
        $this->console("Priority: {$priority} - Queue: {$queue}");
        $this->times->startTime = microtime(true);
        $this->priority = $priority;
        $this->queue = $queue;

        //--------------------------------------------------------------------------------------------------------------

        if ($this->errorHandler->getCriticalExceptionCount() > CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            $this->console('Exceptions are above the ERROR_COUNT_THRESHOLD.');
            exit();
        }

        // fetch companion tokens
        $this->fetchCompanionTokens();

        // fetch item ids to update
        $this->fetchItemIdsToUpdate($priority, $queue, $patreonQueue);

        // initialize Companion API
        $api = new CompanionApi();
        $api->useAsync();

        // 1st pass - send queue requests for all Item Prices + History
        $a     = microtime(true);
        $total = count($this->items);
        foreach ($this->items as $i => $item) {
            $i = $i + 1;
            
            $itemId = $item['item'];
            $server = $item['server'];

            /** @var CompanionToken $token */
            $token  = $this->tokens[$server];
            $api->Token()->set($token->getToken());

            // build requests (PRICES, HISTORY)
            $requests = [
                Uuid::uuid4()->toString() => $api->Market()->getItemMarketListings($itemId),
                Uuid::uuid4()->toString() => $api->Market()->getTransactionHistory($itemId),
            ];

            // store requests
            $this->requests[$server . $itemId] = $requests;

            // send requests and wait
            $api->Sight()->settle($requests)->wait();
            $this->console("({$i}/{$total}) Sent queue requests for: {$itemId}");

            // record requests on Google Analytics
            GoogleAnalytics::companionTrackItemAsUrl("/prices/{$itemId}");
            GoogleAnalytics::companionTrackItemAsUrl("/history/{$itemId}");
            
            usleep(CompanionConfiguration::DELAY_BETWEEN_REQUESTS_MS * 1000);
        }
        $this->times->firstPass = microtime(true) - $a;

        // delay if the 1st pass was fast.
        $firstPassDelay = 25 - ceil($this->times->firstPass);
        
        $this->console("1st Pass = {$this->times->firstPass} seconds");
        if ($firstPassDelay > 0) {
            $this->console("Waiting after first pass of: {$firstPassDelay} seconds");
            sleep($firstPassDelay);
        }

        // 2nd pass - request results of all Item Prices + History
        $a = microtime(true);
        foreach ($this->items as $item) {
            // if exceptions were thrown in any request, we stop
            // (store market updates exceptions if any thrown)
            if ($this->exceptions >= CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
                $this->console('Ending as exceptions have internally hit the limit.');
                break;
            }

            $id     = $item['id'];
            $itemId = $item['item'];
            $server = $item['server'];

            // grab request
            $requests = $this->requests[$server . $itemId];

            // request them again
            $results = $api->Sight()->settle($requests)->wait();
            $this->console("Fetch queue responses for: {$itemId}");

            // save data
            $this->storeMarketData($item, $results);

            // record requests on Google Analytics
            GoogleAnalytics::companionTrackItemAsUrl("/prices/{$itemId}");
            GoogleAnalytics::companionTrackItemAsUrl("/history/{$itemId}");

            // update item entry
            $this->marketItemEntryUpdated[] = [
                $id,
                $patreonQueue
            ];
        }

        // update the database market entries with the latest updated timestamps
        $this->updateDatabaseMarketItemEntries();
        $this->em->flush();

        // finish, output completed duration
        $duration = round(microtime(true) - $this->times->startTime, 1);
        $this->times->secondPass = microtime(true) - $a;
        $this->console("-> Completed. Duration: <comment>{$duration}</comment>");
    }

    /**
     * Store the market data
     */
    private function storeMarketData($item, $results)
    {
        $itemId = $item['item'];
        $server = $item['server'];

        // grab request keys
        $requestKeys = array_keys($this->requests[$server . $itemId]);

        // grab prices and history from response
        /** @var \stdClass $prices */
        /** @var \stdClass $history */
        $prices  = $results[$requestKeys[0]];
        $history = $results[$requestKeys[1]];

        if (isset($prices->error)) {
            $this->errorHandler->exception(
                $prices->reason,
                "Prices: {$itemId} / {$server}"
            );
        }

        if (isset($history->error)) {
            $this->errorHandler->exception(
                $prices->reason,
                "History: {$itemId} / {$server}"
            );
        }

        // if responses null or both have errors
        if (
            ($prices === null && $history == null) ||
            (isset($prices->error) && isset($history->error))
        ) {
            // Analytics
            GoogleAnalytics::companionTrackItemAsUrl('companion_empty');
            $this->console("!!! EMPTY RESPONSE");
            return;
        }

        // grab market item document
        $marketItem = $this->getMarketItemDocument($server, $itemId);
    
        // record lodestone info
        $marketItem->LodestoneID = $prices->eorzeadbItemId;

        // ---------------------------------------------------------------------------------------------------------
        // CURRENT PRICES
        // ---------------------------------------------------------------------------------------------------------
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

        // ---------------------------------------------------------------------------------------------------------
        // CURRENT HISTORY
        // ---------------------------------------------------------------------------------------------------------
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

        // record update
        $this->em->persist(
            new CompanionMarketItemUpdate($itemId, $server, $this->priority)
        );
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
        $limit = implode(',', [
            CompanionConfiguration::MAX_ITEMS_PER_CRONJOB * $queue,
            CompanionConfiguration::MAX_ITEMS_PER_CRONJOB
        ]);

        // get items to update
        $this->console('Finding Item IDs to Auto-Update');

        // patreon get their own table.
        $tableName = $patreonQueue ? "companion_market_item_patreon" : "companion_market_item_entry";

        $sql = "
            SELECT id, item, server FROM {$tableName}
            WHERE priority = {$priority}
            LIMIT {$limit}
        ";

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();

        $this->items = $stmt->fetchAll();
        $this->console('-> Complete');
    }

    /**
     * Fetch the companion tokens.
     */
    private function fetchCompanionTokens()
    {
        /** @var CompanionToken[] $tokens */
        $tokens = $this->em->getRepository(CompanionToken::class)->findAll();

        foreach ($tokens as $token) {
            // skip offline or expired tokens
            if ($token->isOnline() === false) {
                continue;
            }

            $id = GameServers::getServerId($token->getServer());
            $this->tokens[$id] = $token;
        }

        $this->em->clear();
    }

    /**
     * Update item entry
     */
    private function updateDatabaseMarketItemEntries()
    {
        $this->console('Updating database item entries');
        foreach ($this->marketItemEntryUpdated as $item) {
            [$id, $patreonQueue] = $item;

            $tableName = $patreonQueue ? "companion_market_item_patreon" : "companion_market_item_entry";

            $sql = "UPDATE {$tableName} SET updated = ". time() ." WHERE id = '{$id}'";

            $stmt = $this->em->getConnection()->prepare($sql);
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
}
