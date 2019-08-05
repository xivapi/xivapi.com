<?php

namespace App\Controller;

use App\Exception\ApiUnauthorizedAccessException;
use App\Service\API\ApiPermissions;
use App\Service\API\ApiRequest;
use App\Common\Service\Redis\Redis;
use Companion\CompanionApi;
use Companion\Config\SightToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Private access to Companion's API "Sight" directly, requires special access.
 *
 * @package App\Controller
 */
class CompanionController extends AbstractController
{
    const REGION_EU = 'https://companion-eu.finalfantasyxiv.com';
    const REGION_NA = 'https://companion-na.finalfantasyxiv.com';
    const REGION_JP = 'https://companion-jp.finalfantasyxiv.com';

    const CACHE_DURATION = 300;

    /** @var CompanionApi */
    private $api;

    public function __construct()
    {
        $this->api = new CompanionApi(ApiRequest::$idStatic);
    }

    /**
     * @param Request $request
     * @throws ApiUnauthorizedAccessException
     */
    private function setToken(Request $request)
    {
        $requestToken = trim($request->get('token'));

        if (empty($requestToken)) {
            throw new ApiUnauthorizedAccessException('Please provide your Companion Token: ?token=<TOKEN>');
        }

        $requestRegion = trim($request->get('region'));

        if (empty($requestToken)) {
            throw new ApiUnauthorizedAccessException('Please provide your Account Region: ?region=<na|eu|jp>');
        }

        $regions = [
            'eu' => self::REGION_EU,
            'na' => self::REGION_NA,
            'jp' => self::REGION_JP
        ];

        // set token
        $token = new SightToken();
        $token->token = $requestToken;
        $token->region = $regions[$requestRegion] ?? null;

        if ($token->region === null) {
            throw new ApiUnauthorizedAccessException('Invalid region provided, please provide: ?region=<na|eu|jp>');
        }

        $this->api->Token()->set($token);
    }

    /**
     * @Route("/companion/token")
     */
    public function token()
    {
        return $this->json([
            'LoginUrl'     => $this->api->Account()->getLoginUrl(),
            'Token'        => $this->api->Token()->get(),
        ]);
    }

    /**
     * @Route("/companion/login")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws ApiUnauthorizedAccessException
     * @throws \Exception
     */
    public function login(Request $request)
    {
        $username = $request->get('username');

        if (empty($username)) {
            throw new ApiUnauthorizedAccessException('Please provide your username: ?username=<USERNAME>');
        }

        $password = $request->get('password');

        if (empty($password)) {
            throw new ApiUnauthorizedAccessException('Please provide your username: ?password=<PASSWORD>');
        }

        // login
        $this->api->Account()->login($username, $password);

        return $this->json([
            'Token' => $this->api->Token()->get()
        ]);
    }

    /**
     * @Route("/companion/characters")
     * @throws ApiUnauthorizedAccessException
     */
    public function getCharacters(Request $request)
    {
        $this->setToken($request);

        return $this->json([
            'Characters' => $this->api->Login()->getCharacters(),
        ]);
    }

    /**
     * @Route("/companion/character/login")
     * @throws ApiUnauthorizedAccessException
     */
    public function loginCharacter(Request $request)
    {
        $this->setToken($request);

        $characterId = $request->get('character_id');

        if (empty($characterId)) {
            throw new ApiUnauthorizedAccessException('Please provide your Character ID to login with: ?character_id=<ID>');
        }

        $response = [
            'CharacterLogin' => $this->api->Login()->loginCharacter($request->get('character_id')),
            'CharacterWorlds' => $this->api->Login()->getCharacterWorlds()
        ];

        return $this->json($response);
    }

    /**
     * @Route("/companion/market/item/prices")
     * @throws ApiUnauthorizedAccessException
     * @throws \Companion\Exceptions\CompanionServerException
     */
    public function getItemMarketPrices(Request $request)
    {
        $this->setToken($request);

        $itemId = $request->get('item_id');

        if (empty($itemId)) {
            throw new ApiUnauthorizedAccessException('Please provide a Item ID: ?item_id=<ID>');
        }

        $server = $request->get('server');

        if (empty($server)) {
            throw new ApiUnauthorizedAccessException('Please provide a server: ?server=<name>');
        }

        return $this->json([
            'Market' => $this->api->Market()->getItemMarketListings($itemId, $server)
        ]);
    }

    /**
     * @Route("/companion/market/item/history")
     * @throws ApiUnauthorizedAccessException
     * @throws \Companion\Exceptions\CompanionServerException
     */
    public function getItemMarketHistory(Request $request)
    {
        $this->setToken($request);

        $itemId = $request->get('item_id');

        if (empty($itemId)) {
            throw new ApiUnauthorizedAccessException('Please provide a Item ID: ?item_id=<ID>');
        }

        $server = $request->get('server');

        if (empty($server)) {
            throw new ApiUnauthorizedAccessException('Please provide a server: ?server=<name>');
        }

        return $this->json([
            'Market' => $this->api->Market()->getTransactionHistory($itemId, $server)
        ]);
    }
}
