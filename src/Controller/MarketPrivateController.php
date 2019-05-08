<?php

namespace App\Controller;

use App\Entity\CompanionItem;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionTokenManager;
use App\Service\Companion\Updater\MarketUpdater;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use App\Service\Redis\RedisTracking;
use Companion\CompanionApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Private access to Companion's API "Sight" directly, requires special access.
 *
 * @package App\Controller
 */
class MarketPrivateController extends AbstractController
{
    /** @var CompanionTokenManager */
    private $companionTokenManager;
    /** @var MarketUpdater */
    private $companionMarketUpdater;
    
    public function __construct(
        CompanionTokenManager $companionTokenManager,
        MarketUpdater $companionMarketUpdater
    ) {
        $this->companionTokenManager  = $companionTokenManager;
        $this->companionMarketUpdater = $companionMarketUpdater;
    }
    
    /**
     * @Route("/private/market/item")
     */
    public function itemPrices(Request $request)
    {
        if ($request->get('access') !== getenv('MB_ACCESS')) {
            throw new UnauthorizedHttpException('Denied');
        }
        
        $itemId = (int)$request->get('item_id');
        $server = (int)GameServers::getServerId(ucwords($request->get('server')));
        $key    = "companion_private_query_prices_{$itemId}_{$server}";
        
        if ($response = Redis::Cache()->get($key)) {
            return $this->json($response);
        }
        
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);
        $api    = new CompanionApi();
        $api->Token()->set((Object)$token->getToken());
        $response = $api->Market()->getItemMarketListings($itemId);
        Redis::Cache()->set($key, $response, 60);
        
        return $this->json($response);
    }
    
    /**
     * @Route("/private/market/item/history")
     */
    public function itemHistory(Request $request)
    {
        if ($request->get('access') !== getenv('MB_ACCESS')) {
            throw new UnauthorizedHttpException('Denied');
        }
        
        $itemId = (int)$request->get('item_id');
        $server = (int)GameServers::getServerId(ucwords($request->get('server')));
        $key    = "companion_private_query_history_{$itemId}_{$server}";
        
        if ($response = Redis::Cache()->get($key)) {
            return $this->json($response);
        }
        
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);
        $api    = new CompanionApi();
        $api->Token()->set((Object)$token->getToken());
        $response = $api->Market()->getTransactionHistory($itemId);
        Redis::Cache()->set($key, $response, 60);
        
        return $this->json($response);
    }
    
    /**
     * This is used by alerts for DPS users.
     * @Route("/private/market/item/update")
     */
    public function manualUpdateItem(Request $request)
    {
        if ($request->get('access') !== getenv('MB_ACCESS')) {
            throw new UnauthorizedHttpException('Denied');
        }
        
        $itemId = (int)$request->get('item_id');
        $server = (int)$request->get('server');
        $dc     = GameServers::getDataCenter(GameServers::LIST[$server]);
    
        /**
         * Check the server isn't an offline one
         */
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            return $this->json([ false, 0, 'The server provided is currently not supported.' ]);
        }
        
        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
        
        /**
         * First, check if the item was passed here already and is on a timeout.
         */
        if (Redis::Cache()->get("companion_market_manual_queue_check_{$itemId}_{$dc}")) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item has already been requested very recently to be updated, it should update shortly.'
            ]);
        }
    
        /**
         * Check if the item is in a patreon queue, if so it will update soon
         */
        if ($marketEntry->getPatreonQueue() > 0) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item is in a patron queue and will be updated shortly.'
            ]);
        }
        
        /**
         * Check when the item was last updated, maybe it updated within the last 5 minutes,
         * if so then we don't need to update it again.
         * - Currently 15 minutes
         */
        $lastUpdatedLimitMinutes = 15;
        $lastUpdatedLimitSeconds = (60 * $lastUpdatedLimitMinutes);
        if ($marketEntry->getUpdated() > (time() - $lastUpdatedLimitSeconds)) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                "Item was updated recently (within {$lastUpdatedLimitMinutes} minutes) and cannot be updated at this time."
            ]);
        }
    
        // timeout based on queue
        $timeoutTimes = [
            1 => 15, // 1 hour queue
            2 => 30, // 3 hour queue
            3 => 60, // 12 hour queue
            4 => 60, // 30 hour queue
            5 => 60, // 48 hour queue
            6 => 60  // default
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
    
        RedisTracking::increment('TOTAL_DPS_ALERTS_UPDATES');
        RedisTracking::append('TOTAL_DPS_ALERTS_UPDATES', date('Y-m-d H:i:s'));
        
        /**
         * if we have a queue, use it, otherwise pick oen at random
         */
        $queue = $queue ? $queue : $queues[array_rand($queues)];
        $this->companionMarketUpdater->updateManual($itemId, $server, $queue);
        
        return $this->json([
            true,
            time(),
            "Item will be updated by patreon queue: {$queue}"
        ]);
    }
    
    /**
     * @Route("/private/market/item/update/requested")
     */
    public function manualUpdateItemRequested(Request $request)
    {
        if ($request->get('access') !== getenv('MB_ACCESS')) {
            throw new UnauthorizedHttpException('Denied');
        }
        
        $itemId = (int)$request->get('item_id');
        $server = (int)$request->get('server');
        $dc     = GameServers::getDataCenter(GameServers::LIST[$server]);
    
        /**
         * Check the server isn't an offline one
         */
        if (in_array($server, GameServers::MARKET_OFFLINE)) {
            return $this->json([
                false,
                0,
                'The server provided is currently not supported.'
            ]);
        }
    
        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
    
        // if the item was manually updated within the hour, ignore
        if (Redis::Cache()->get("companion_item_dc_update_preupdate_{$itemId}_{$dc}")) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item has already been requested to be updated, it should update shortly.'
            ]);
        }
        
        // if the item was manually updated within the hour, ignore
        if (Redis::Cache()->get("companion_item_dc_update_custom_{$itemId}_{$dc}")) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item was recently placed in the update queue and should be up to date or will be very shortly. Check back in a few minutes for up to date prices.'
            ]);
        }
        
        /**
         * Check if the item was updated manually
         */
        if (Redis::Cache()->get("companion_market_manual_queue_check_{$itemId}_{$dc}")) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item was updated very recently and cannot be queued to update at this time.'
            ]);
        }
    
        /**
         * Check if the item is in a patreon queue, if so it will update soon
         */
        if ($marketEntry->getPatreonQueue() > 0) {
            return $this->json([
                false,
                $marketEntry->getUpdated(),
                'This item is in a patron queue and will be updated shortly.'
            ]);
        }
    
        // timeout based on queue
        $timeoutTimes = [
            1 => 15, // 1 hour queue
            2 => 30, // 3 hour queue
            3 => 60, // 12 hour queue
            4 => 60, // 30 hour queue
            5 => 60, // 48 hour queue
            6 => 60  // default
        ];
    
        $timeout = 60 * $timeoutTimes[$marketEntry->getNormalQueue()] ?? 60;
    
        // Place the item on this server in a cooldown based on its queue number
        Redis::Cache()->set("companion_market_manual_queue_check_{$itemId}_{$dc}", time(), $timeout);
        
        // Place the item on a "manual update" cooldown as well
        Redis::Cache()->set("companion_item_dc_update_custom_{$itemId}_{$dc}", time(), $timeout);
    
        // Place the item on a very short cooldown prior to "updating"
        Redis::Cache()->set("companion_item_dc_update_preupdate_{$itemId}_{$dc}", time(), (60 * 5));
        
        // get servers for this DC
        $servers = GameServers::getDataCenterServers(GameServers::LIST[$server]);
        
        // mark all on the DC to update
        foreach ($servers as $server) {
            $server = GameServers::getServerId($server);
            $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
            $marketEntry->setPriority(0);
            $this->companionMarketUpdater->saveMarketItemEntry($marketEntry);
        }
    
        RedisTracking::increment('TOTAL_MANUAL_UPDATES');
        RedisTracking::append('TOTAL_MANUAL_UPDATES', date('Y-m-D H:i:s'));
        
        return $this->json([
            true,
            time(),
            "Item has been bumped to the front of the queue. Please allow up to 5 minutes for the system to process the request and for Prices + History on ALL servers within your Data-Center to be updated."
        ]);
    }
}
