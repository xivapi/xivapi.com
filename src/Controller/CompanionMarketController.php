<?php

namespace App\Controller;

use App\Service\Companion\Companion;
use App\Service\Companion\CompanionMarket;
use App\Service\Content\GameServers;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class CompanionMarketController extends Controller
{
    /** @var CompanionMarket */
    private $companionMarket;
    /** @var Companion */
    private $companion;
    
    public function __construct(Companion $companion, CompanionMarket $companionMarket)
    {
        $this->companion = $companion;
        $this->companionMarket = $companionMarket;
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}")
     * @Route("/market/{server}/item/{itemId}")
     */
    public function item(Request $request, string $server, int $itemId)
    {
        return $this->json(
            $this->companionMarket->get(
                GameServers::getServerId($server),
                $itemId,
                $request->get('max_history') ?: 50
            )
        );
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
     * @Route("/v2/market/search")
     */
    public function test()
    {
        return $this->json(
            $this->companionMarket->search()
        );
    }
}
