<?php

namespace App\Controller;

use App\Service\Companion\CompanionMarketPrivate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Private access to Companion's API "Sight" directly, requires special access.
 *
 * @package App\Controller
 */
class MarketPrivateController extends AbstractController
{
    /** @var CompanionMarketPrivate */
    private $cmp;
    
    public function __construct(CompanionMarketPrivate $cmp)
    {
        $this->cmp  = $cmp;
    }
    
    /**
     * @Route("/private/market/item")
     */
    public function itemPrices(Request $request)
    {
        return $this->json(
            $this->cmp->setRequest($request)->getItemPrices()
        );
    }
    
    /**
     * @Route("/private/market/item/history")
     */
    public function itemHistory(Request $request)
    {
        return $this->json(
            $this->cmp->setRequest($request)->getItemHistory()
        );
    }
    
    /**
     * This is used by alerts for DPS users.
     * @Route("/private/market/item/update")
     */
    public function manualUpdateItem(Request $request)
    {
        return $this->json(
            $this->cmp->setRequest($request)->manualUpdateItem()
        );
    }
    
    /**
     * @Route("/private/market/item/update/requested")
     */
    public function manualUpdateItemRequested(Request $request)
    {
        return $this->json(
            $this->cmp->setRequest($request)->manualUpdateItemRequested()
        );
    }
    
    /**
     * @Route("/market/retainer")
     */
    public function retainerItems(Request $request)
    {
        return $this->json(
            $this->cmp->getRetainerItems($request->get('retainer_id'))
        );
    }
    
    /**
     * @Route("/market/character")
     */
    public function characterHistory(Request $request)
    {
        return $this->json(
            $this->cmp->getCharacterHistory($request->get('lodestone_id'))
        );
    }
    
    /**
     * @Route("/market/signature")
     */
    public function signatureItems(Request $request)
    {
        return $this->json(
            $this->cmp->getCharacterSignatures($request->get('lodestone_id'))
        );
    }
}
