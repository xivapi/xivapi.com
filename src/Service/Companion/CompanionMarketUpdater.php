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
use App\Repository\CompanionRetainerRepository;
use App\Repository\CompanionSignatureRepository;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use Companion\CompanionApi;
use Companion\Config\CompanionSight;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Auto-Update item price + history
 */
class CompanionMarketUpdater
{
    const MAX_PER_ASYNC         = 40;
    const MAX_PER_CHUNK         = 2;
    const MAX_CRONJOB_DURATION  = 50;
    const MAX_QUERY_SLEEP_SEC   = 3;
    
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
        $this->console = new ConsoleOutput();
    }
    
    public function update(int $priority, int $queue)
    {
        $this->start = time();

        // random sleep at start, this is so not all queries against sight start at the same time.
        usleep( mt_rand(100, 3000) * 1000 );
    
        // grab our companion tokens
        $this->tokens = $this->companionTokenManager->getCompanionTokensPerServer();
    
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
                $this->console->writeln(date('H:i:s') .' | Ending auto-update as time limit seconds reached.');
                return;
            }
        
            // handle the chunk
            $this->updateChunk($i, $itemChunk);
        }
    
        $this->em->clear();
    }

    /**
     * Update a group of items
     */
    private function updateChunk($chunkNumber, $chunkList)
    {
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
            
            // if token expired OR token offline
            if ($api->Token()->hasExpired($token->getLastOnline()) || $token->isOnline() === false) {
                continue;
            }

            // set the Sight token for these requests (required so it switches server)
            $api->Token()->set($token->getToken());
            
            // add requests
            $requests["{$itemId}_{$server}_prices"]  = $api->Market()->getItemMarketListings($itemId);
            $requests["{$itemId}_{$server}_history"] = $api->Market()->getTransactionHistory($itemId);
        }
        
        // if failed to pull any requests, skip!
        if (empty($requests)) {
            return;
        }

        $totalRequests = count($requests);
        $this->console->writeln(date('H:i:s') ." | Processing chunk: {$chunkNumber} - Total Requests: {$totalRequests}");
        
        // run the requests, we don't care on response because the first time nothing will be there.
        $this->console->writeln(date('H:i:s') ." | <info>Part 1: Sending Requests</info>");

        // 1st pass
        $api->Sight()->settle($requests)->wait();
    
        // Wait for the results
        sleep(self::MAX_QUERY_SLEEP_SEC);
        
        // run the requests again, the Sight API should give us our response this time.
        $this->console->writeln(date('H:i:s') ." | <info>Part 2: Fetching Responses</info>");

        // second pass
        $results = $api->Sight()->settle($requests)->wait();

        // handle the results of the response
        $results = $api->Sight()->handle($results);
        $this->storeMarketData($chunkList, $results);
    }
    
    /**
     * Update a chunk of items to the document storage
     */
    private function storeMarketData($chunkList, $results)
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
            
            if (isset($prices->error)) {
                $this->recordException('prices', $itemId, $server, $prices->reason);
            }
            
            if (isset($history->error)) {
                $this->recordException('history', $itemId, $server, $history->reason);
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
        
            $this->console->writeln(date('H:i:s') ." | <comment>✓</comment> Updated prices + history for item: {$itemId} on {$server}");
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
}
