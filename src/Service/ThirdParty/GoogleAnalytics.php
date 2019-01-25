<?php

namespace App\Service\ThirdParty;

use App\Entity\User;
use App\Entity\UserApp;
use App\Service\Common\Language;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interact with Google Analytics
 * Guide: https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 *
 * Route tracking needs to be done manually to avoid capturing PID URLs or
 * any URL that contains any form of ID (eg Dev Apps)
 */
class GoogleAnalytics
{
    const ENDPOINT      = 'http://www.google-analytics.com';
    const VERSION       = 1;
    const TIMEOUT       = 5;

    public static function getClient()
    {
        return new Client([
            'base_uri' => self::ENDPOINT,
            'timeout'  => self::TIMEOUT
        ]);
    }

    /**
     * @param array $options
     */
    public static function query(array $options)
    {
        try {
            self::getClient()->post('/collect', [
                RequestOptions::QUERY => $options
            ]);
        } catch (\Exception $ex) {
            // ignore
        }
    }
    
    /**
     * Post a hit to Google Analytics
     */
    public static function hit(string $trackingId, string $url): void
    {
        $trackingId = str_ireplace('{XIVAPI}', getenv('SITE_CONFIG_GOOGLE_ANALYTICS'), $trackingId);

        self::query([
            't'   => 'pageview',
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'z'   => mt_rand(0, 999999),

            'tid' => $trackingId,
            'dp'  => $url,
        ]);
    }

    /**
     * Record an event
     */
    public static function event(string $trackingId, string $category, string $action, string $label = '', int $value = 1): void
    {
        $trackingId = str_ireplace('{XIVAPI}', getenv('SITE_CONFIG_GOOGLE_ANALYTICS'), $trackingId);

        self::query([
            't'   => 'event',
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'z'   => mt_rand(0, 999999),

            'tid' => $trackingId,
            'ec'  => $category,
            'ea'  => $action,
            'el'  => $label,
            'ev'  => $value,
        ]);
    }

    // --------------------------------
    // -- common tracking events
    // --------------------------------

    public static function trackHits(Request $request)
    {
        self::hit('{XIVAPI}', $request->getPathInfo());
    }

    public static function trackBaseEndpoint(Request $request)
    {
        self::event('{XIVAPI}', 'Requests', 'Endpoint', explode('/', $request->getPathInfo())[1] ?? 'Home');
    }

    public static function trackLanguage()
    {
        self::event('{XIVAPI}', 'Requests', 'Language', Language::current());
    }

    public static function trackUserBanned(User $user)
    {
        self::event('{XIVAPI}', 'Denied', 'User Banned',"{$user->getUsername()}");
    }

    public static function trackAppBanned(UserApp $userApp)
    {
        self::event('{XIVAPI}', 'Denied', 'API Key Banned', "{$userApp->getApiKey()}");
    }

    public static function trackAppUsage(UserApp $userApp)
    {
        self::event('{XIVAPI}', 'Apps', $userApp->getApiKey(), "{$userApp->getName()} - {$userApp->getUser()->getUsername()}");
    }

    public static function trackAppRouteAccess(UserApp $userApp, Request $request)
    {
        self::event('{XIVAPI}', 'Endpoints', "{$userApp->getApiKey()}", $request->getPathInfo());
    }
}
