<?php

namespace App\Controller;

use App\Service\Companion\CompanionMarketUpdater;
use App\Service\Companion\CompanionTokenManager;
use App\Service\Redis\Redis;
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
    /** @var CompanionMarketUpdater */
    private $companionMarketUpdater;

    public function __construct(
        CompanionTokenManager $companionTokenManager,
        CompanionMarketUpdater $companionMarketUpdater
    ) {
        $this->companionTokenManager  = $companionTokenManager;
        $this->companionMarketUpdater = $companionMarketUpdater;
    }

    /**
     * @Route("/private/market/item")
     */
    public function itemPrices(Request $request)
    {
        if ($request->get('companion_access_key') !== getenv('SITE_CONFIG_COMPANION_TOKEN_PASS')) {
            throw new UnauthorizedHttpException('Denied');
        }

        $itemId = (int)$request->get('item_id');
        $server = ucwords($request->get('server'));
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);

        $api = new CompanionApi();
        $api->Token()->set($token->getToken());

        return $this->json(
            $api->Market()->getItemMarketListings($itemId)
        );
    }

    /**
     * @Route("/private/market/item/history")
     */
    public function itemHistory(Request $request)
    {
        if ($request->get('companion_access_key') !== getenv('SITE_CONFIG_COMPANION_TOKEN_PASS')) {
            throw new UnauthorizedHttpException('Denied');
        }

        $itemId = (int)$request->get('item_id');
        $server = ucwords($request->get('server'));
        $token  = $this->companionTokenManager->getCompanionTokenForServer($server);

        $api = new CompanionApi();
        $api->Token()->set($token->getToken());

        return $this->json(
            $api->Market()->getTransactionHistory($itemId)
        );
    }

    /**
     * @Route("/private/market/item/update")
     */
    public function manualUpdateItem(Request $request)
    {
        if ($request->get('companion_access_key') !== getenv('SITE_CONFIG_COMPANION_TOKEN_PASS')) {
            throw new UnauthorizedHttpException('Denied');
        }

        $itemId = (int)$request->get('item_id');
        $server = ucwords($request->get('server'));

        // max set to 99
        $queue = 99;
        foreach (range(1, 5) as $num) {
            $status = Redis::Cache()->get("companion_market_manual_queue_{$num}");

            if ($status === null) {
                $queue = $num;
                break;
            }
        }

        Redis::Cache()->set("companion_market_manual_queue_{$queue}", true, 60);
        $this->companionMarketUpdater->updateManual($itemId, $server, $queue);
        return $this->json(true);
    }
}
