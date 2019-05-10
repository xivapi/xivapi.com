<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionCharacter;
use App\Entity\CompanionItem;
use App\Entity\CompanionRetainer;
use App\Entity\CompanionToken;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionItemRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionErrorHandler;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use App\Service\Redis\RedisTracking;
use App\Service\ThirdParty\Discord\Discord;
use App\Service\ThirdParty\Discord\Mog;
use App\Service\ThirdParty\GoogleAnalytics;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Companion\CompanionApi;
use Companion\Config\CompanionSight;
use Companion\Config\SightToken;
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
    }
    
    public function update(int $queue)
    {
        //
        // todo - tempz?
        //
        if (in_array(date('i'), [7,8])) {
            $this->console("Not doing any queries as it's 7/8 minutes past");
            return;
        }

        //
        // todo - temp
        //
        $japan = Carbon::now(new CarbonTimeZone('Asia/Tokyo'));
        $this->console->writeln("Hour: {$japan->hour}");
        switch($japan->hour) {
            default: $pause = false; break;
            case 8: $pause = mt_rand(0, 2); break;
            case 9: $pause = mt_rand(1, 3); break;
            case 10: $pause = mt_rand(2, 5); break;
            case 11: $pause = mt_rand(2, 6); break;
            case 12: $pause = mt_rand(3, 10); break;
            case 13: $pause = mt_rand(3, 12); break;
            case 14: $pause = mt_rand(3, 10); break;
            case 15: $pause = mt_rand(2, 8); break;
            case 16: $pause = mt_rand(1, 5); break;
            case 17: $pause = mt_rand(1, 4); break;
            case 18: $pause = mt_rand(0, 3); break;
        }
        //
        // todo - temp
        //

        // init
        $this->console("Queue: {$queue}");
        $this->startTime = microtime(true);
        $this->deadline = time() + CompanionConfiguration::CRONJOB_TIMEOUT_SECONDS;
        $this->queue = $queue;
        $this->console('Starting!');

        // fetch tokens and items
        $this->fetchCompanionTokens();
        $this->fetchItemIdsToUpdate($queue);
    
        // no items? da fookz
        if (empty($this->items)) {
            $this->console('No items to update');
            $this->closeDatabaseConnection();
            return;
        }
        
        // initialize companion api
        $api = new CompanionApi();
        
        // settings
        CompanionSight::set('CLIENT_TIMEOUT', 2.5);
        CompanionSight::set('QUERY_LOOP_COUNT', 5);
        CompanionSight::set('QUERY_DELAY_MS', mt_rand(950,1250));
        
        // begin
        foreach ($this->items as $item) {
            // deeds
            $itemId = $item['item'];
            $serverId = $item['server'];
            $serverName = GameServers::LIST[$serverId];
            $serverDc = GameServers::getDataCenter($serverName);

            try {
                $a = microtime(true);
    
                // Break if any errors or we're at the cronjob deadline
                if ($this->checkErrorCount() || $this->checkScriptDeadline()) {
                    break;
                }

                if (!isset($this->tokens[$serverId]) || empty($this->tokens[$serverId])) {
                    $this->console("No tokens for: {$serverName} {$serverDc}");
                    continue;
                }
    
                // pick a random token
                $token = $this->tokens[$serverId][array_rand($this->tokens[$serverId])];

                // record
                RedisTracking::increment('COMPANION_ACCOUNT_USAGE_'. $token->account);

                // set token
                $api->Token()->set($token);
    
                /**
                 * GET PRICES
                 */
                $prices = $api->Market()->getItemMarketListings($itemId);
                if ($this->checkResponseForErrors($item, $prices)) {
                    break;
                }
    
                /**
                 * GET HISTORY
                 */
                $history = $api->Market()->getTransactionHistory($itemId);
                if ($this->checkResponseForErrors($item, $history)) {
                    break;
                }
    
                /**
                 * Store in market
                 */
                $this->storeMarketData($item, $prices, $history);
    
                /**
                 * Record item updates in analytics
                 */
                GoogleAnalytics::companionTrackItemAsUrl("/{$itemId}");
    
                /**
                 * Log
                 */
                $duration = round(microtime(true) - $a, 1);
                $this->console("{$itemId} on {$serverName} - {$serverDc} - Duration: {$duration}");
    
                RedisTracking::increment('ITEM_UPDATED');
                RedisTracking::increment('ITEM_UPDATED_DAILY_'. $queue .'_'. date('y-m-d'));
                RedisTracking::increment('ITEM_UPDATED_DAILY_'. (strlen($queue) == 3 ? substr($queue, 0, 1) : 'PATRON') .'_'. date('y-m-d'));
    
                if ($pause) {
                    sleep($pause);
                }
            } catch (\Exception $ex) {
                // log all errors
                file_put_contents(__DIR__.'/../../../../CompanionErrors.log', "{$itemId} on {$serverName} - {$serverDc} ERROR: {$ex->getMessage()}", FILE_APPEND);
                $this->console("{$itemId} on {$serverName} - {$serverDc} ERROR: {$ex->getMessage()}");
    
                $this->errorHandler->exception(
                    $ex->getMessage(),
                    "Item Update Failure for: ({$token->account}) {$itemId} on {$serverName} - {$serverDc}"
                );
                
                // if emergency maintenance or "congestion" logout that server
                if (stripos($ex->getMessage(), '319201') !== false ||
                    stripos($ex->getMessage(), '210010') !== false) {
                    // mark item as updated
                    $this->marketItemEntryUpdated[] = $item['id'];
                    $this->logoutCharacterServers("Maintenance/Congestion", $serverName);
                    break;
                }

                // if unauthorised, logout that specific account
                if (stripos($ex->getMessage(), '111001') !== false) {
                    $this->logoutAccount("Authorization failed", $token->account);
                    break;
                }
            }
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
     * Checks for any problems in the response
     */
    private function checkResponseForErrors($item, $response)
    {
        $itemId     = $item['item'];
        $serverId   = $item['server'];
        $serverName = GameServers::LIST[$serverId];
        $serverDc   = GameServers::getDataCenter($serverName);
        
        if (isset($response->state) && $response->state == "rejected") {
            $this->console("Response Rejected");
            $this->errorHandler->exception("Rejected", "RESPONSE REJECTED: {$itemId} : ({$serverId}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_rejected');
            return true;
        }
    
        if (isset($response->error) || isset($response->error)) {
            $this->console("Response Error");
            $this->errorHandler->exception($response->reason, "RESPONSE ERROR: {$itemId} : ({$serverId}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_error');
            return true;
        }
    
        // if responses are null
        if ($response == null) {
            $this->console("Response Empty");
            $this->errorHandler->exception('Empty Response', "RESPONSE EMPTY: {$itemId} : ({$serverId}) {$serverName} - {$serverDc}");
            GoogleAnalytics::companionTrackItemAsUrl('companion_empty');
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks the current critical exception rate
     */
    private function checkErrorCount()
    {
        if ($this->errorHandler->isCriticalExceptionCount()) {
            $this->console('Exceptions are above the ERROR_COUNT_THRESHOLD.');
            return true;
        }
        
        return false;
    }
    
    /**
     * Tests to see if the time deadline has hit
     */
    private function checkScriptDeadline()
    {
        // if we go over the deadline, we stop.
        if (time() > $this->deadline) {
            $this->console(date('H:i:s') ." | Ending auto-update as time limit seconds reached.");
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout a character is congestion is detected
     */
    private function logoutCharacterServers(string $message, string $serverName)
    {
        // update expiring to 60-180 mins
        $expiring = time() + (60 * mt_rand(60,180));

        $sql = "
            UPDATE companion_tokens
            SET online = 0, message = 'Auto Logout: {$message}', token = NULL, expiring = {$expiring}
            WHERE server = '{$serverName}'
        ";
    
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
    
        $date = date('Y-m-d H:i:s');
        Discord::mog()->sendMessage(
            '571007332616503296',
            "[{$date} UTC] **Server-Wide Account Logout: {$message}** - Logged out server: {$serverName}"
        );
    }
    
    /**
     * Logout a character is congestion is detected
     */
    private function logoutAccount(string $message, string $account)
    {
        // update expiring to 60-180 mins
        $expiring = time() + (60 * mt_rand(60,180));
        
        $sql = "
            UPDATE companion_tokens
            SET online = 0, message = 'Auto Logout: {$message}', token = NULL, expiring = {$expiring}
            WHERE account = '{$account}'
        ";
        
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        
        $date = date('Y-m-d H:i:s');
        Discord::mog()->sendMessage(
            '571007332616503296',
            "[{$date} UTC] **Account: {$account} has been automatically logged out: {$message}**"
        );
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
    
        // update item entry
        $this->marketItemEntryUpdated[] = $item['id'];
    
        // grab market item document
        $marketItem = $this->getMarketItemDocument($server, $itemId);
    
        // record lodestone info
        $marketItem->LodestoneID = $prices->eorzeadbItemId;

        // reset prices (always do this)
        $marketItem->Prices = [];

        // CURRENT PRICES
        if (isset($prices->error) === false && isset($prices->entries) && $prices->entries) {
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
     * Get a single market item entry.
     */
    public function getMarketItemEntry(int $serverId, int $itemId)
    {
        return $this->em->getRepository(CompanionItem::class)->findOneBy([
            'server' => $serverId,
            'item'   => $itemId,
        ]);
    }
    
    /**
     * Save a companion market item
     */
    public function saveMarketItemEntry(CompanionItem $companionItem)
    {
        $this->em->persist($companionItem);
        $this->em->flush();
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
     * Fetches items to auto-update, this is performed here as the entity
     * manager is quite slow for thousands of throughput every second.
     */
    private function fetchItemIdsToUpdate($queue)
    {
        // get items to update
        $this->console('Finding Item IDs to Auto-Update');
        $s = microtime(true);

        $sql = "
            SELECT id, item, server
            FROM companion_market_item_queue
            WHERE queue = {$queue}
            LIMIT ". CompanionConfiguration::MAX_ITEMS_PER_CRONJOB;
        
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();

        $this->items = $stmt->fetchAll();
        
        $sqlDuration = round(microtime(true) - $s, 2);
        $this->console("Obtained items in: {$sqlDuration} seconds");
    }

    /**
     * Fetch the companion tokens, this will randomly pick one for each server
     */
    private function fetchCompanionTokens()
    {
        $conn = $this->em->getConnection();
        $sql  = "SELECT account, server, online, token FROM companion_tokens";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        foreach ($stmt->fetchAll() as $arr) {
            $serverId = GameServers::getServerId($arr['server']);
    
            // skip offline tokens
            if ($arr['online'] == 0) {
                continue;
            }
            
            if (!isset($this->tokens[$serverId])) {
                $this->tokens[$serverId] = [];
            }

            $token = json_decode($arr['token']);
            $token->account = $arr['account'];
            $this->tokens[$serverId][] = $token;
        }
    }

    /**
     * Update item entry
     */
    private function updateDatabaseMarketItemEntries()
    {
        $this->console('Updating database item entries');
        $conn = $this->em->getConnection();
        
        $priority = time() + mt_rand(0,1000);

        foreach ($this->marketItemEntryUpdated as $id) {
            $sql = "UPDATE companion_market_items SET updated = ". time() .", priority = ". $priority .", patreon_queue = NULL WHERE id = '{$id}'";

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
     * Mark an item to be manually updated on an DC
     */
    public function updateManual(int $itemId, int $server, int $queueNumber)
    {
        /** @var CompanionItemRepository $repo */
        $repo    = $this->em->getRepository(CompanionItem::class);
        $servers = GameServers::getDataCenterServersIds(GameServers::LIST[$server]);
        $items   = $repo->findItemsInServers($itemId, $servers);
        
        /** @var CompanionItem $item */
        foreach ($items as $item) {
            $item->setPatreonQueue($queueNumber);
            $this->em->persist($item);
        }
        
        $this->em->flush();
    }
}
