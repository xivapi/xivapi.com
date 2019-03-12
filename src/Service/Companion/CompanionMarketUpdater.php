<?php

namespace App\Service\Companion;

use App\Entity\CompanionCharacter;
use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionRetainer;
use App\Entity\CompanionSignature;
use App\Entity\CompanionToken;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Repository\CompanionRetainerRepository;
use App\Repository\CompanionSignatureRepository;
use App\Service\Common\Arrays;
use App\Service\Common\Mog;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use Companion\CompanionApi;
use Companion\Config\CompanionSight;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Auto-Update item price + history
 */
class CompanionMarketUpdater
{
    const MAX_PER_ASYNC         = 50;
    const MAX_PER_CHUNK         = 2;
    const MAX_CRONJOB_DURATION  = 55;
    const MAX_QUERY_SLEEP_SEC   = 2500;

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
    /** @var CompanionSignatureRepository */
    private $repositoryCompanionSignature;
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
        $this->repositoryCompanionSignature = $this->em->getRepository(CompanionSignature::class);
        $this->repositoryExceptions = $this->em->getRepository(CompanionMarketItemException::class);
        $this->console = new ConsoleOutput();
    }
    
    public function update(int $priority, int $queue)
    {
        if ($this->hasExceptionsExceededLimit()) {
            $this->console->writeln(date('H:i:s') .' | !! Error exceptions exceeded limit. Auto-Update stopped');
            exit();
        }

        $this->start = time();

        // random sleep at start, this is so not all queries start at the same time.
        usleep( mt_rand(10, 800) * 1000 );
    
        // grab our companion tokens
        $this->tokens = $this->companionTokenManager->getCompanionTokensPerServer();
        
        if (empty($this->tokens)) {
            $this->console->writeln(date('H:i:s') .' | All tokens have expired, cannot auto-update');
            return;
        }
    
        /** @var CompanionMarketItemEntry[] $entries */
        $items = $this->repository->findItemsToUpdate(
            $priority,
            self::MAX_PER_ASYNC,
            self::MAX_PER_ASYNC * $queue,
            array_keys($this->tokens)
        );
        
        $this->console->writeln(date('H:i:s') .' | Total items to update: '. count($items));
    
        // loop through chunks
        foreach (array_chunk($items, self::MAX_PER_CHUNK) as $i => $itemChunk) {
            // if we're close to the cronjob minute mark, end
            if ((time() - $this->start) > self::MAX_CRONJOB_DURATION) {
                $this->console->writeln(date('H:i:s') ." | [{$priority}] Ending auto-update as time limit seconds reached.");
                return;
            }
        
            // handle the chunk
            $this->updateChunk($i, $itemChunk, $priority);
        }
    
        $this->em->clear();
    }

    /**
     * Update a group of items
     */
    private function updateChunk($chunkNumber, $chunkList, $priority)
    {
        // set request id
        $requestId = Uuid::uuid4()->toString();
        
        // initialize Companion API, no token provided as we set it later on
        // also enable async
        $api = new CompanionApi();
        $api->useAsync();

        // a single item will not update faster than X minutes.
        $updateTimeout = time() - CompanionItemManagerPriorityTimes::ITEM_UPDATE_DELAY;
        
        /** @var CompanionMarketItemEntry $item */
        $requests = [];
        foreach ($chunkList as $item) {
            // skip items that have been updated recently
            if ($item->getUpdated() > $updateTimeout) {
                $this->console->writeln(date('H:i:s') ." | [{$priority}] Skipped: {$item->getItem()}");
                continue;
            }

            $itemId = $item->getItem();
            $server = $item->getServer();
            
            /** @var CompanionToken $token */
            $token  = $this->tokens[$server];

            // set the Sight token for these requests (required so it switches server)
            $api->Token()->set($token->getToken());
            
            // add requests
            $requests["{$requestId}_{$itemId}_{$server}_prices"]  = $api->Market()->getItemMarketListings($itemId);
            $requests["{$requestId}_{$itemId}_{$server}_history"] = $api->Market()->getTransactionHistory($itemId);
        }
        
        // if failed to pull any requests, skip!
        if (empty($requests)) {
            return;
        }

        $totalRequests = count($requests);
        $this->console->writeln(date('H:i:s') ." | [{$priority}] Processing chunk: {$chunkNumber} - Total Requests: {$totalRequests}");
        
        // run the requests, we don't care on response because the first time nothing will be there.
        $this->console->writeln(date('H:i:s') ." | [{$priority}] <info>Part 1: Sending Requests</info>");

        // 1st pass
        $api->Sight()->settle($requests)->wait();
    
        // Wait for the results
        usleep( self::MAX_QUERY_SLEEP_SEC * 1000 );
        
        // run the requests again, the Sight API should give us our response this time.
        $this->console->writeln(date('H:i:s') ." | [{$priority}] <info>Part 2: Fetching Responses</info>");

        // second pass
        $results = $api->Sight()->settle($requests)->wait();

        // handle the results of the response
        $results = $api->Sight()->handle($results);
        $this->storeMarketData($chunkList, $results, $requestId, $priority);
    }
    
    /**
     * Update a chunk of items to the document storage
     */
    private function storeMarketData($chunkList, $results, $requestId, $priority)
    {
        // process the chunk list from our results
        /** @var CompanionMarketItemEntry $item */
        foreach ($chunkList as $item) {
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
                $this->console->writeln(date('H:i:s') ." | [{$priority}] Price + History empty: {$item->getItem()}");
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
                    // grab internal records
                    $row->_retainerId = $this->getInternalRetainerId($row->sellRetainerName);
                    $row->_creatorSignatureId = $this->getInternalSignatureId($row->signatureName);
                    
                    // append prices
                    $marketItem->Prices[] = MarketListing::build($row);
                }
            }
        
            // ---------------------------------------------------------------------------------------------------------
            // CURRENT HISTORY
            // ---------------------------------------------------------------------------------------------------------
            if ($history && isset($history->error) === false && $history->history) {
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
    
                    // grab internal record
                    $row->_characterId = $this->getInternalCharacterId($row->buyCharacterName);
                
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
        
            $this->console->writeln(date('H:i:s') ." | [{$priority}] <comment>✓</comment> Updated prices + history for item: {$itemId} on {$server}");
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
     * Record failed queries
     */
    private function recordException($type, $itemId, $server, $error)
    {
        $this->console->writeln(date('H:i:s') ." !!! EXCEPTION: {$type}, {$itemId}, {$server}");

        $exception = new CompanionMarketItemException();
        $exception->setException("{$type}, {$itemId}, {$server}")->setMessage($error);
        
        $this->em->persist($exception);
        $this->em->flush();
    }
    
    /**
     * Returns the ID for internally stored retainers
     */
    private function getInternalRetainerId(string $name): ?string
    {
        if (empty($name)) {
            return null;
        }
        
        $obj = $this->repositoryCompanionRetainer->findOneBy([ 'name' => $name ]);
        
        if ($obj === null) {
            $obj = new CompanionRetainer($name);
            $this->em->persist($obj);
        }
        
        return $obj->getId();
    }
    
    /**
     * Returns the ID for internally stored signature ids
     */
    private function getInternalSignatureId(string $name): ?string
    {
        if (empty($name)) {
            return null;
        }
        
        $obj = $this->repositoryCompanionSignature->findOneBy([ 'name' => $name ]);
    
        if ($obj === null) {
            $obj = new CompanionSignature($name);
            $this->em->persist($obj);
        }
    
        return $obj->getId();
    }
    
    /**
     * Returns the ID for internally stored character ids
     */
    private function getInternalCharacterId(string $name): ?string
    {
        if (empty($name)) {
            return null;
        }
        
        $obj = $this->repositoryCompanionCharacter->findOneBy([ 'name' => $name ]);
    
        if ($obj === null) {
            $obj = new CompanionCharacter($name);
            $this->em->persist($obj);
        }
    
        return $obj->getId();
    }

    /**
     * Returns true if there is an exception threshold met
     */
    private function hasExceptionsExceededLimit()
    {
        $exceptions = $this->repositoryExceptions->findAll();

        // limit of 1 set for now just for monitoring purposes.
        if (empty($exceptions) || count($exceptions) < 1) {
            return false;
        }

        /** @var CompanionMarketItemException $ex */
        $errors = [];
        foreach ($exceptions as $ex) {
            $date     = date('Y-m-d H:i:s', $ex->getAdded());
            $errors[] = "# [{$date}] {$ex->getException()} \n {$ex->getMessage()}";
        }

        $errors = implode("\n\n", $errors);

        $message = '<@42667995159330816> Item-Update shutdown due to error exceptions exceeding limit.';
        $message .= "\n```markdown\n{$errors}\n````";

        if (Redis::Cache()->get('companion_market_updator_mog_warning') == null) {
            Redis::Cache()->set('companion_market_updator_mog_warning', 'true', 14400);
            Mog::send($message);
        }

        return true;
    }
}
