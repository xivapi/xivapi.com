<?php

namespace App\Service\Companion;

use App\Entity\CompanionCharacter;
use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionMarketItemUpdate;
use App\Entity\CompanionRetainer;
use App\Entity\CompanionToken;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
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
class CompanionMarketUpdater
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarketItemEntryRepository */
    private $repository;
    /** @var CompanionCharacterRepository */
    private $repositoryCompanionCharacter;
    /** @var CompanionRetainerRepository */
    private $repositoryCompanionRetainer;
    /** @var CompanionMarketItemExceptionRepository */
    private $repositoryExceptions;
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
    /** @var int */
    private $deadline;
    /** @var int */
    private $updateCount = 0;
    /** @var int */
    private $exceptionCount = 0;
    /** @var int */
    private $chunkStartTime;
    /** @var float */
    private $gaDuration;
    /** @var float */
    private $companionDuration;
    
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
        $this->repositoryCompanionCharacter = $this->em->getRepository(CompanionCharacter::class);
        $this->repositoryCompanionRetainer = $this->em->getRepository(CompanionRetainer::class);
        $this->repositoryExceptions = $this->em->getRepository(CompanionMarketItemException::class);
        $this->console = new ConsoleOutput();
    }
    
    public function update(int $priority, int $queue, int $patreonQueue = null)
    {
        $this->start    = time();
        $this->deadline = time() + CompanionConfiguration::CRONJOB_TIMEOUT_SECONDS;

        $this->console->writeln(date('H:i:s') .' | A');
        $this->console->writeln("Priority: {$priority} - Queue: {$queue} - Patreon Queue: {$patreonQueue}");
        $this->console->writeln("Deadline: ". date('H:i:s', $this->deadline));

        $queueStartTime = microtime(true);
        if ($this->hasExceptionsExceededLimit()) {
            $this->console->writeln(date('H:i:s') .' | !! Error exceptions exceeded limit. Auto-Update stopped');
            exit();
        }

        // grab our companion tokens
        $this->tokens = $this->companionTokenManager->getCompanionTokensPerServer();
        $this->console->writeln(date('H:i:s') .' | B');

        if (empty($this->tokens)) {
            $this->console->writeln(date('H:i:s') .' | All tokens have expired, cannot auto-update');
            return;
        }
    
        /** @var CompanionMarketItemEntry[] $entries */
        $start = CompanionConfiguration::MAX_ITEMS_PER_CRONJOB;
        $limit = CompanionConfiguration::MAX_ITEMS_PER_CRONJOB * $queue;

        $this->console->writeln(date('H:i:s') .' | GETTING ITEMS');
        $items = $patreonQueue
            ? $this->repository->findPatreonItemsToUpdate($patreonQueue, $start, $limit)
            : $this->repository->findItemsToUpdate($priority, $start, $limit);
        $this->console->writeln(date('H:i:s') .' | GOT ITEMS');
        
        // loop through chunks
        foreach (array_chunk($items, CompanionConfiguration::MAX_ITEMS_PER_REQUEST) as $i => $itemChunk) {
            // if we go over the deadline, we stop.
            if (time() > $this->deadline) {
                $this->console->writeln(date('H:i:s') ." | [{$priority}] (Updates: {$this->updateCount}) Ending auto-update as time limit seconds reached.");
                break;
            }
            
            if ($this->exceptionCount > CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
                $this->console->writeln(date('H:i:s') .' | !! Error exceptions (real-time check) exceeded limit. Auto-Update stopped');
                break;
            }
        
            // handle the chunk
            $this->updateChunk($itemChunk, $priority);
        }
    
        $this->em->clear();
        
        // report
        $duration = round(microtime(true) - $queueStartTime, 2);
        $this->console->writeln(date('H:i:s') ." | (Updates: {$this->updateCount}) Finished queue: {$priority}:{$queue} - Duration: {$duration}");
    }

    /**
     * Mark an item to be manually updated on an DC
     */
    public function updateManual(int $itemId, int $server, int $queueNumber)
    {
        $servers = GameServers::getDataCenterServersIds(GameServers::LIST[$server]);
        $items   = $this->repository->findItemsInServers($itemId, $servers);

        /** @var CompanionMarketItemEntry $item */
        foreach ($items as $item) {
            $item->setPatreonQueue($queueNumber);
            $this->em->persist($item);
        }

        $this->em->flush();
    }

    /**
     * Get a single market item entry.
     */
    public function getMarketItemEntry(int $serverId, int $itemId)
    {
        return $this->repository->findOneBy([
            'server' => $serverId,
            'item'   => $itemId,
        ]);
    }

    /**
     * Update a group of items
     */
    private function updateChunk($chunkList, $priority)
    {
        $this->chunkStartTime = microtime(true);
        
        // set request id
        $requestId = Uuid::uuid4()->toString();
        
        // initialize Companion API, no token provided as we set it later on
        // also enable async
        $api = new CompanionApi();
        $api->useAsync();

        /** @var CompanionMarketItemEntry $item */
        $requests = [];
        foreach ($chunkList as $item) {
            $itemId = $item->getItem();
            $server = $item->getServer();
            
            /** @var CompanionToken $token */
            $token  = $this->tokens[$server];

            // set the Sight token for these requests (required so it switches server)
            $api->Token()->set($token->getToken());
            
            // add requests
            $requests["{$requestId}_{$itemId}_{$server}_prices"]  = $api->Market()->getItemMarketListings($itemId);
            $requests["{$requestId}_{$itemId}_{$server}_history"] = $api->Market()->getTransactionHistory($itemId);
    
            $a = microtime(true);
            GoogleAnalytics::companionTrackItemAsUrl($itemId);
            $this->gaDuration = round(microtime(true) - $a, 2);
        }
        
        // if failed to pull any requests, skip!
        if (empty($requests)) {
            return;
        }

        // 1st pass
        $companionStart   = microtime(true);
        $api->Sight()->settle($requests)->wait();
        $secondPassFinish = (microtime(true) - $companionStart) * 1000;

        // Wait for the results, this dynamically adjusts basedo n how long the 1st pass took.
        usleep(
            (CompanionConfiguration::CRONJOB_ASYNC_DELAY_MS - $secondPassFinish) * 1000
        );

        // 2nd pass (this will have the request results)
        $results = $api->Sight()->settle($requests)->wait();

        // handle the results of the response
        $results = $api->Sight()->handle($results);

        $this->companionDuration = round(microtime(true) - $companionStart, 1);

        // Store the results
        $this->storeMarketData($chunkList, $results, $requestId, $priority);
    }
    
    /**
     * Update a chunk of items to the document storage
     */
    private function storeMarketData($chunkList, $results, $requestId, $priority)
    {
        // process the chunk list from our results
        /** @var CompanionMarketItemEntry $item */
        foreach ($chunkList as $i => $item) {
            $itemId = $item->getItem();
            $server = $item->getServer();
        
            // grab our prices and history
            /** @var \stdClass $prices */
            /** @var \stdClass $history */
            $prices  = $results->{"{$requestId}_{$itemId}_{$server}_prices"} ?? null;
            $history = $results->{"{$requestId}_{$itemId}_{$server}_history"} ?? null;
            
            if (isset($prices->error)) {
                $this->recordException('prices', $itemId, $server, $prices->reason);
            }
            
            if (isset($history->error)) {
                $this->recordException('history', $itemId, $server, $history->reason);
            }
    
            // if responses null or both have errors
            if (
                ($prices === null && $history == null) ||
                (isset($prices->error) && isset($history->error))
            ) {
                // Analytics
                GoogleAnalytics::companionTrackItemAsUrl('companion_empty');
                $this->console->writeln("Empty response!");
                return;
            }
        
            // grab market item document
            $marketItem = $this->getMarketItemDocument($item);
        
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
            }
            
            // put
            $this->companionMarket->set($marketItem);
    
            $duration = round(microtime(true) - $this->chunkStartTime, 1);
    
            $msg = date('H:i:s') ." | ";
            $msg .= sprintf("Item: <comment>%s</comment>", str_pad($itemId, 12, ' '));
            $msg .= sprintf("Server: <comment>%s</comment>", str_pad(GameServers::LIST[$server], 20, ' '));
            $msg .= sprintf("Duration: <comment>%s</comment>", str_pad($duration, 15, ' '));
            $msg .= sprintf("Companion: <comment>%s</comment>", str_pad($this->companionDuration, 15, ' '));
            $msg .= sprintf("GA Duration: %s", $this->gaDuration);
            
            // record
            $this->recordUpdate($priority, $itemId, $server, $duration);
        
            // update entry
            $this->em->persist(
                $item
                    ->setUpdated(time())
                    ->setPatreonQueue(null)
            );

            $this->console->writeln($msg);
            $this->updateCount++;
        }

        $this->em->flush();
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
     * Record an item update
     */
    private function recordUpdate($priority, $item, $server, $duration)
    {
        // this is technically divided by number we do concurrently
        $duration = round($duration / CompanionConfiguration::MAX_ITEMS_PER_REQUEST, 2);
        
        $this->em->persist(
            new CompanionMarketItemUpdate($item, $server, $priority, $duration)
        );
    }
    
    /**
     * Record failed queries
     */
    private function recordException($type, $itemId, $server, $error)
    {
        // Analytics
        GoogleAnalytics::companionTrackItemAsUrl('companion_error');

        $this->console->writeln(date('H:i:s') ." !!! EXCEPTION: {$type}, {$itemId}, {$server}");
        $this->exceptionCount++;

        $exception = new CompanionMarketItemException();
        $exception->setException("{$type}, {$itemId}, {$server}")->setMessage($error);
        $this->em->persist($exception);
        $this->em->flush();

        $recentErrorCount = count($this->repositoryExceptions->findAllRecent());
        $maxErrorCount = CompanionConfiguration::ERROR_COUNT_THRESHOLD;

        // discord msg
        $serverName = GameServers::LIST[$server];
        $errorSimple = str_ireplace('GuzzleHttp\Exception\ServerException -- ', null, $error);

        $type = ucwords($type);
        $item = Redis::Cache()->get("xiv_Item_{$itemId}");

        $discordEmbed = [
            'description'   => "```{$errorSimple}```",
            'color'         => hexdec('f44242'),
            'author'        => [
                'name' => 'Companion Auto-Update Error',
                'icon_url' => 'https://xivapi.com/discord/offline.png',
            ],
            'fields' => [
                [
                    'name'   => 'Error Type',
                    'value'  => $type,
                    'inline' => true,
                ],
                [
                    'name'   => 'Error Count',
                    'value'  => "{$recentErrorCount} / {$maxErrorCount}",
                    'inline' => true,
                ],
                [
                    'name'   => 'Item (ID/Name)',
                    'value'  => "{$itemId} - {$item->Name_en}",
                    'inline' => true,
                ],
                [
                    'name'   => 'Server (ID/Name)',
                    'value'  => "{$server} - {$serverName}",
                    'inline' => true,
                ]
            ]
        ];

        Discord::mog()->sendMessage(null, '<@42667995159330816>', $discordEmbed);
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
     * Returns true if there is an exception threshold met, only counted against exceptions within
     * the past hour.
     */
    private function hasExceptionsExceededLimit()
    {
        $exceptions = $this->repositoryExceptions->findAllRecent();

        // limit of 1 set for now just for monitoring purposes.
        if (empty($exceptions) || count($exceptions) < CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            return false;
        }

        return true;
    }
}
