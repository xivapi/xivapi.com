<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
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
    
        if ($request->get('access') != getenv('MB_ACCESS')) {
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
        $itemId = (int)$this->request->get('item_id');
        $server = (int)$this->request->get('server');
        $dc     = GameServers::getDataCenter(GameServers::LIST[$server]);

        // redis timeout key
        $timeoutkey = "mogboard_dps_item_update_timeout_{$itemId}_{$dc}";

        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);
    
        // First, check if the item was passed here already and is on a timeout.
        if (Redis::Cache()->get($timeoutkey)) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item has already been requested very recently to be updated, it should update shortly.'
            ];
        }

        // Place item on a 15 minute cooldown
        Redis::Cache()->set($timeoutkey, true, 900);

        // request item update
        $this->companionMarketUpdater->updatePatreon($itemId, $server);

        return [
            true,
            time(),
            "Item will be updated shortly."
        ];
    }
    
    /**
     * Manually update an item
     * (this one is usually sent by normal users)
     */
    public function manualUpdateItemRequested()
    {
        $itemId  = (int)$this->request->get('item_id');
        $server  = (int)$this->request->get('server');
        $dc      = GameServers::getDataCenter(GameServers::LIST[$server]);

        // redis timeout key
        $timeoutkey = "mogboard_dps_item_update_timeout_{$itemId}_{$dc}";

        /** @var CompanionItem $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);

        // First, check if the item was passed here already and is on a timeout.
        if (Redis::Cache()->get($timeoutkey)) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item was updated (or requested to be) in the last 15 minutes. Please check in a few minutes to see if it has updated.'
            ];
        }

        /**
         * Check if the item is in a patreon queue, if so it will update soon
         */
        if ($marketEntry->getPatreonQueue() > 0) {
            return [
                false,
                $marketEntry->getUpdated(),
                'This item is in a patreon queue and will be updated within the next minute, refresh the page in a bit!'
            ];
        }

        // Place item on a 15 minute cooldown
        Redis::Cache()->set($timeoutkey, true, 900);

        // request item update
        $this->companionMarketUpdater->updateManual($itemId, $server);

        return [
            true,
            time(),
            "Item has been bumped to the front of the queue. Please allow a couple minutes for the system to process the request and for Prices + History on ALL servers within your Data-Center to be updated."
        ];
    }
}
