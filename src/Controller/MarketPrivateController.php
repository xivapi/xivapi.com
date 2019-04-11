<?php

namespace App\Controller;

use App\Entity\CompanionMarketItemEntry;
use App\Service\Companion\CompanionMarketUpdater;
use App\Service\Companion\CompanionTokenManager;
use App\Service\Content\GameServers;
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

        /** @var CompanionMarketItemEntry $marketEntry */
        $marketEntry = $this->companionMarketUpdater->getMarketItemEntry($server, $itemId);

        /**
         * First, check if the item was passed here within the past 5 minutes.
         */
        $requestLastSent = Redis::Cache()->get("companion_market_manual_queue_check_{$itemId}_{$server}");

        if ($requestLastSent) {
            return $this->json([ false, $requestLastSent, 'Item already requested to be updated within the past 5 minutes.' ]);
        }

        // Place the item on this server in a 300 second cooldown
        Redis::Cache()->set("companion_market_manual_queue_check_{$itemId}_{$server}", time(), 300);

        /**
         * Check when the item was last updated, maybe it updated within the last 5 minutes,
         * if so then we don't need to update it again.
         */
        if ($marketEntry->getUpdated() > (time() - (60 * 5))) {
            return $this->json([ false, $marketEntry->getUpdated(), 'Item already updated within the past 5 minutes' ]);
        }

        /**
         * Find an empty queue
         */
        $queue  = null;
        $queues = range(1, 5);
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
        return $this->json([
            true,
            time(),
            "Item will be updated by patreon queue: {$queue}"
        ]);
    }
}
