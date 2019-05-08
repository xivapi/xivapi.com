<?php

namespace App\Controller;

use App\Exception\ApiUnauthorizedAccessException;
use App\Service\API\ApiPermissions;
use App\Service\API\ApiRequest;
use App\Service\Redis\Redis;
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

    private function setToken(Request $request)
    {
        $requestToken = trim($request->get('token'));

        if (empty($requestToken)) {
            throw new ApiUnauthorizedAccessException();
        }

        // set token
        $token = new SightToken();
        $token->token = $requestToken;
        $this->api->Token()->set($token);
    }

    /**
     * Will either return the cache result or set it if a response is provided
     */
    private function cache($method, $response = null)
    {
        if ($response) {
            $key  = $method . ApiRequest::$idStatic;
            Redis::Cache()->set($key, $response, self::CACHE_DURATION);
            return null;
        }

        $key  = $method . ApiRequest::$idStatic;

        if ($response = Redis::Cache()->get($key)) {
            return $response;
        }

        return null;
    }

    /**
     * Handle response
     */
    private function response($method, $response)
    {
        $response['Cached'] = time();
        $response['CacheExpires'] = time() + self::CACHE_DURATION;

        $this->cache($method, $response);

        return $this->json($response);
    }

    /**
     * Request a Companion "Sight" Token and build a Login URL using
     * the provided UID for that token.
     *
     * @Route("/companion/token")
     */
    public function token()
    {
        ApiPermissions::must(ApiPermissions::PERMISSION_COMPANION);

        if ($response = $this->cache(__METHOD__)) {
            return $this->json($response);
        }

        return $this->response(__METHOD__, [
            'LoginUrl'     => $this->api->Account()->getLoginUrl(),
            'Token'        => $this->api->Token()->get(),
            'Cached'       => time(),
            'CacheExpires' => time() + 300,
        ]);
    }

    /**
     * @Route("/companion/characters")
     */
    public function characters(Request $request)
    {
        ApiPermissions::must(ApiPermissions::PERMISSION_COMPANION);

        if ($response = $this->cache(__METHOD__)) {
            return $this->json($response);
        }

        $this->setToken($request);

        return $this->response(__METHOD__, [
            'Characters' => $this->api->Login()->getCharacters(),
        ]);
    }
}
