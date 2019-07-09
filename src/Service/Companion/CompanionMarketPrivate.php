<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Common\Service\Redis\RedisTracking;
use App\Entity\CompanionItem;
use App\Service\Companion\Updater\MarketUpdater;
use Companion\CompanionApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CompanionMarketPrivate
{
    /** @var Request */
    private $request;
    /** @var CompanionTokenManager */
    private $companionTokenManager;
    /** @var CompanionMarket */
    private $companionMarket;
    /** @var MarketUpdater */
    private $companionMarketUpdater;
    
    public function __construct(
        CompanionTokenManager $companionTokenManager,
        CompanionMarket $companionMarket,
        MarketUpdater $companionMarketUpdater
    ) {
        $this->companionTokenManager  = $companionTokenManager;
        $this->companionMarketUpdater = $companionMarketUpdater;
        $this->companionMarket        = $companionMarket;
    }
    
    public function setRequest(Request $request)
    {
        $this->request = $request;
    
        if ($request->get('access') !== getenv('MB_ACCESS')) {
            throw new UnauthorizedHttpException('Denied');
        }
        
        return $this;
    }
    
    /**
     * Get item prices
     */
    public function getItemPrices()
    {
        $itemId = (int)$this->request->get('item_id');
        $server = (int)GameServers::getServerId(ucwords($this->request->get('server')));
        
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            throw new \Exception('The server provided is currently not supported.');
        }
        
        $key = __METHOD__ . $itemId . $server;
        
        if ($data = Redis::Cache()->get($key)) {
            return $data;
        }
    
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);
        $api    = new CompanionApi();
        $api->Token()->set((Object)$token->getToken());
    
        $data = $api->Market()->getItemMarketListings($itemId);
        Redis::Cache()->set($key, $data, 60);
        return $data;
    }
    
    /**
     * Get data history
     */
    public function getItemHistory()
    {
        $itemId = (int)$this->request->get('item_id');
        $server = (int)GameServers::getServerId(ucwords($this->request->get('server')));
    
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            throw new \Exception('The server provided is currently not supported.');
        }
    
        $key = __METHOD__ . $itemId . $server;
    
        if ($data = Redis::Cache()->get($key)) {
            return $data;
        }
    
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);
        $api    = new CompanionApi();
        $api->Token()->set((Object)$token->getToken());
    
        $data = $api->Market()->getTransactionHistory($itemId);
        Redis::Cache()->set($key, $data, 60);
        return $data;
    }
    
    /**
     * @param $retainerId
     */
    public function getRetainerItems(string $retainerId)
    {
        return $this->companionMarket->getRetainerItems($retainerId);
    }
    
    /**
     * @param $retainerId
     */
    public function getCharacterHistory(string $lodestoneId)
    {
        return $this->companionMarket->getCharacterHistory($lodestoneId);
    }
    
    /**
     * @param $retainerId
     */
    public function getCharacterSignatures(string $lodestoneId)
    {
        return $this->companionMarket->getCharacterSignatureItems($lodestoneId);
    }
    
    /**
     * Manually update an item
     * (this one is usually sent by DPS Alerts)
     */
    public function manualUpdateItem()
    {
        RedisTracking::increment('TOTAL_DPS_ALERTS_INITIALIZED');
    
        $itemId = (int)$this->request->get('item_id');
        $server = (int)$this->request->get('server');
        $dc     = GameServers::getDataCenter(GameServers::LIST[$server]);
    
        /**
         * Check the server isn't an offline one
         */
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            return [ false, 0, 'The server provided is currently not supported.' ];
        }
    
        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
    
        /**
         * First, check if the item was passed here already and is on a timeout.
         */
        if (Redis::Cache()->get("companion_market_manual_queue_check_{$itemId}_{$dc}")) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item has already been requested very recently to be updated, it should update shortly.'
            ];
        }
    
        /**
         * Check if the item is in a patreon queue, if so it will update soon
         */
        if ($marketEntry->getPatreonQueue() > 0) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item is in a patron queue and will be updated shortly.'
            ];
        }
    
        /**
         * Check when the item was last updated, maybe it updated within the last 5 minutes,
         * if so then we don't need to update it again.
         * - Currently 15 minutes
         */
        $lastUpdatedLimitMinutes = 5;
        $lastUpdatedLimitSeconds = (60 * $lastUpdatedLimitMinutes);
        if ($marketEntry->getUpdated() > (time() - $lastUpdatedLimitSeconds)) {
            return [
                false,
                $marketEntry->getUpdated(),
                "Item was updated recently (within {$lastUpdatedLimitMinutes} minutes) and cannot be updated at this time."
            ];
        }
    
        // timeout based on queue
        $timeoutTimes = [
            1 => 15, // 1 hour queue
            2 => 15, // 3 hour queue
            3 => 15, // 12 hour queue
            4 => 15, // 30 hour queue
            5 => 15, // 48 hour queue
            6 => 15  // default
        ];
    
        $timeout = 60 * $timeoutTimes[$marketEntry->getNormalQueue()] ?? 60;
    
        // Place the item on this server in a cooldown based on its queue number
        Redis::Cache()->set("companion_market_manual_queue_check_{$itemId}_{$dc}", time(), $timeout);
    
        /**
         * Pick a random queue, it should distribute mostly... evenly.
         */
        $queue  = null;
        $queues = range(
            min(CompanionConfiguration::QUEUE_CONSUMERS_PATREON),
            max(CompanionConfiguration::QUEUE_CONSUMERS_PATREON)
        );
    
        // try look for an empty queue, if one isn't found it'll pick one at random
        foreach ($queues as $i => $num) {
            $size = Redis::Cache()->get("companion_market_manual_queue_{$num}");
        
            if ($size === null) {
                Redis::Cache()->set("companion_market_manual_queue_{$num}", 1, 60);
                $queue = $num;
                break;
            }
        }
    
        /**
         * if we have a queue, use it, otherwise pick oen at random
         */
        $queue = $queue ? $queue : $queues[array_rand($queues)];
        $this->companionMarketUpdater->updatePatreon($itemId, $server, $queue);
    
        RedisTracking::increment('TOTAL_DPS_ALERTS_UPDATES');
        RedisTracking::increment("TOTAL_DPS_ALERTS_UPDATES_QUEUE_{$queue}");

        return [
            true,
            time(),
            "Item will be updated by patreon queue: {$queue}"
        ];
    }
    
    /**
     * Manually update an item
     * (this one is usually sent by normal users)
     */
    public function manualUpdateItemRequested()
    {
        RedisTracking::increment('TOTAL_MANUAL_UPDATES_CLICKED');

        $itemId  = (int)$this->request->get('item_id');
        $server  = (int)$this->request->get('server');
        $dc      = GameServers::getDataCenter(GameServers::LIST[$server]);

        /**
         * Check the server isn't an offline one
         */
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            return [
                false,
                0,
                'The server provided is currently not supported.'
            ];
        }
    
        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
    
        // if the item was manually updated within the hour, ignore
        if (Redis::Cache()->get("companion_item_dc_update_preupdate_{$itemId}_{$dc}")) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item has already been requested to be updated, it should update shortly.'
            ];
        }
    
        // if the item was manually updated within the hour, ignore
        if (Redis::Cache()->get("companion_item_dc_update_custom_{$itemId}_{$dc}")) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item was recently placed in the update queue and should be up to date or will be very shortly. Check back in a few minutes for up to date prices.'
            ];
        }
    
        /**
         * Check if the item was updated manually
         */
        if (Redis::Cache()->get("companion_market_manual_queue_check_{$itemId}_{$dc}")) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item was updated very recently and cannot be queued to update at this time.'
            ];
        }
    
        /**
         * Check if the item is in a patreon queue, if so it will update soon
         */
        if ($marketEntry->getPatreonQueue() > 0) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item is in a patron queue and will be updated shortly.'
            ];
        }
    
        // timeout based on queue
        $timeoutTimes = [
            1 => 15, // 1 hour queue
            2 => 15, // 3 hour queue
            3 => 15, // 12 hour queue
            4 => 15, // 30 hour queue
            5 => 15, // 48 hour queue
            6 => 15  // default
        ];
    
        $timeout = 60 * $timeoutTimes[$marketEntry->getNormalQueue()] ?? 60;
    
        // Place the item on this server in a cooldown based on its queue number
        Redis::Cache()->set("companion_market_manual_queue_check_{$itemId}_{$dc}", time(), $timeout);
    
        // Place the item on a "manual update" cooldown as well
        Redis::Cache()->set("companion_item_dc_update_custom_{$itemId}_{$dc}", time(), $timeout);
    
        // Place the item on a very short cooldown prior to "updating"
        Redis::Cache()->set("companion_item_dc_update_preupdate_{$itemId}_{$dc}", time(), (60 * 3));

        /**
         * Pick a random queue, it should distribute mostly... evenly.
         */
        $queue  = null;
        $queues = range(
            min(CompanionConfiguration::QUEUE_CONSUMERS_MANUAL),
            max(CompanionConfiguration::QUEUE_CONSUMERS_MANUAL)
        );

        // try look for an empty queue, if one isn't found it'll pick one at random
        foreach ($queues as $i => $num) {
            $size = Redis::Cache()->get("companion_market_manual_queue_{$num}");

            if ($size === null) {
                Redis::Cache()->set("companion_market_manual_queue_{$num}", 1, 60);
                $queue = $num;
                break;
            }
        }

        /**
         * if we have a queue, use it, otherwise pick oen at random
         */
        $queue = $queue ? $queue : $queues[array_rand($queues)];
        $this->companionMarketUpdater->updateManual($itemId, $server, $queue);
    
        RedisTracking::increment('TOTAL_MANUAL_UPDATES');

        return [
            true,
            time(),
            "Item has been bumped to the front of the queue. Please allow up to 5 minutes for the system to process the request and for Prices + History on ALL servers within your Data-Center to be updated."
        ];
    }
}
