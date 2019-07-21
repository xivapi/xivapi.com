<?php

namespace App\Controller;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Common\Utils\Arrays;
use App\Entity\CompanionItem;
use App\Entity\CompanionToken;
use App\Exception\InvalidCompanionMarketRequestException;
use App\Exception\InvalidCompanionMarketRequestServerSizeException;
use App\Service\Companion\Companion;
use App\Service\Companion\CompanionErrorHandler;
use App\Service\Companion\CompanionItemManager;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionStatistics;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class MarketController extends AbstractController
{
    const DEFAULT_MAX_HISTORY = 100;
    const DEFAULT_MAX_PRICES  = 50;
    
    /** @var CompanionStatistics */
    private $companionStatistics;
    /** @var CompanionMarket */
    private $companionMarket;
    /** @var CompanionItemManager */
    private $companionItemManager;
    /** @var CompanionErrorHandler */
    private $companionErrorHandler;
    /** @var CompanionTokenManager */
    private $companionTokenManager;
    /** @var Companion */
    private $companion;
    
    public function __construct(
        Companion $companion,
        CompanionMarket $companionMarket,
        CompanionItemManager $companionItemManager,
        CompanionStatistics $companionStatistics,
        CompanionErrorHandler $companionErrorHandler,
        CompanionTokenManager $companionTokenManager
    ) {
        $this->companion                = $companion;
        $this->companionMarket          = $companionMarket;
        $this->companionItemManager     = $companionItemManager;
        $this->companionStatistics      = $companionStatistics;
        $this->companionErrorHandler    = $companionErrorHandler;
        $this->companionTokenManager    = $companionTokenManager;
    }
    
    /**
     * @Route("/market/categories")
     */
    public function categories()
    {
        return $this->json(
            $this->companion->getCategories()
        );
    }
    
    /**
     * @Route("/market/stats", name="market_stats")
     */
    public function statistics()
    {
        return $this->json([
            'Endpoint no longer supported'
        ]);
    }
    
    /**
     * @Route("/market/online", name="market_online")
     */
    public function online()
    {
        $status  = [];
        $online  = $this->companionTokenManager->getOnlineServers();
        
        /** @var CompanionToken $token */
        foreach (GameServers::LIST as $serverId => $server) {
            $status[] = [
                'ID'         => $serverId,
                'Server'     => $server,
                'Online'     => in_array($serverId, $online),
            ];
        }
        
        return $this->json([
            'Servers' => GameServers::LIST,
            'Status'  => $status,
            'Online'  => $online,
        ]);
    }
    
    /**
     * @Route("/market/search")
     */
    public function search(Request $request)
    {
        return $this->json(
            false //$this->companionMarket->search()
        );
    }

    /**
     * @Route("/market/ids")
     */
    public function sellable()
    {
        return $this->json(
            $this->companionItemManager->getMarketItemIds()
        );
    }
    
    /**
     * Obtain price + history for multiple items
     *
     * @Route("/market/items")
     */
    public function itemMulti(Request $request)
    {
        $itemIds = array_filter(explode(',', $request->get('ids')));
        $results = [];
        
        if (count($itemIds) > 100) {
            throw new \Exception("No, too many ids!");
        }
        
        foreach ($itemIds as $id) {
            $results[] = $this->item($request, $id, true);
        }
        
        return $this->json($results);
    }
    
    /**
     * Obtain price + history for an item on multiple servers
     *
     * @Route("/market/item/{itemId}")
     */
    public function item(Request $request, int $itemId, bool $isInternal = false)
    {
        $servers = array_filter(explode(',', $request->get('servers')));
        $dc      = ucwords($request->get('dc'));
        
        if (count($servers) > 15) {
            throw new \Exception("No, too many servers!");
        }
        
        // overwrite servers if a DC is provided
        $servers = $dc ? GameServers::LIST_DC[$dc] : $servers;
        
        // server or dc is empty
        if (empty($servers) && empty($dc)) {
            throw new InvalidCompanionMarketRequestException();
        }
        
        // too many servers
        if (count($servers) > InvalidCompanionMarketRequestServerSizeException::MAX_SERVERS) {
            throw new InvalidCompanionMarketRequestServerSizeException();
        }
        
        // options
        $maxHistory = $request->get('max_history') ?: self::DEFAULT_MAX_HISTORY;
        $maxPrices  = $request->get('max_prices')  ?: self::DEFAULT_MAX_PRICES;
        
        // build response
        $response = [];
        foreach ($servers as $server) {
            $serverId = is_string($server) ? GameServers::getServerId($server) : $server;
            $response[$server] = $this->companionMarket->get($serverId, $itemId, $maxHistory, $maxPrices);
        }
        
        if ($isInternal) {
            return $response;
        }
        
        return $this->json($response);
    }
    
    /**
     * Obtain price + history for an item on a specific server
     *
     * @Route("/market/{server}/items/{itemId}")
     * @Route("/market/{server}/item/{itemId}")
     */
    public function itemByServer(Request $request, string $server, int $itemId)
    {
        // options
        $maxHistory = $request->get('max_history') ?: self::DEFAULT_MAX_HISTORY;
        $maxPrices  = $request->get('max_prices')  ?: self::DEFAULT_MAX_PRICES;
        
        // build response
        $serverId = GameServers::getServerId($server);
        $response = $this->companionMarket->get($serverId, $itemId, $maxHistory, $maxPrices);
        
        return $this->json($response);
    }
    
    /**
     * @Route("/market/tracked")
     */
    public function itemsBeingTracked()
    {
        $items = $this->companionMarket->getTrackedItems();
        
        $response = [
            CompanionItem::STATE_UPDATING => [],
            CompanionItem::STATE_BOUGHT_FROM_NPC => [],
            CompanionItem::STATE_LOW_LEVEL => [],
        ];
        
        foreach ($items as $itemRow) {
            $itemId = $itemRow['item'];
            $queue  = $itemRow['normal_queue'];
            $state  = $itemRow['state'];
            
            $item = Redis::cache()->get("xiv_Item_{$itemId}");
            
            $response[$state][] = [
                'ID' => $itemId,
                'Queue' => $queue,
                'Name' => $item->Name_en,
                'ItemSearchCategory' => $item->ItemSearchCategory->Name_en,
                'ItemKind' => $item->ItemKind->Name_en,
                'LevelEquip' => $item->LevelEquip,
                'LevelItem' => $item->LevelItem,
            ];
        }
    
        Arrays::sortBySubKey($response[CompanionItem::STATE_UPDATING], 'ItemSearchCategory', true);
        Arrays::sortBySubKey($response[CompanionItem::STATE_BOUGHT_FROM_NPC], 'ItemSearchCategory', true);
        Arrays::sortBySubKey($response[CompanionItem::STATE_LOW_LEVEL], 'ItemSearchCategory', true);
        
        return $this->render('_tracked.html.twig', [
            'tracked_items' => $response
        ]);
    }
}
