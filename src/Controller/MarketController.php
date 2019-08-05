<?php

namespace App\Controller;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Common\Utils\Arrays;
use App\Entity\CompanionItem;
use App\Entity\CompanionToken;
use App\Exception\InvalidCompanionMarketRequestException;
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
use App\Common\Exceptions\BasicException;

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
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * @Route("/market/online", name="market_online")
     */
    public function online()
    {
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * @Route("/market/search")
     */
    public function search(Request $request)
    {
        throw new BasicException("Endpoint no longer available.");
    }

    /**
     * @Route("/market/ids")
     */
    public function sellable()
    {
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * Obtain price + history for multiple items
     *
     * @Route("/market/items")
     */
    public function itemMulti(Request $request)
    {
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * Obtain price + history for an item on multiple servers
     *
     * @Route("/market/item/{itemId}")
     */
    public function item(Request $request, int $itemId, bool $isInternal = false)
    {
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * Obtain price + history for an item on a specific server
     *
     * @Route("/market/{server}/items/{itemId}")
     * @Route("/market/{server}/item/{itemId}")
     */
    public function itemByServer(Request $request, string $server, int $itemId)
    {
        throw new BasicException("Endpoint no longer available.");
    }
    
    /**
     * @Route("/market/tracked")
     */
    public function itemsBeingTracked()
    {
        throw new BasicException("Endpoint no longer available.");
    }
}
