<?php

namespace App\Common\ServicesThirdParty\Google;

use App\Service\API\ApiRequest;
use App\Common\Utils\Language;
use Ramsey\Uuid\Uuid;

/**
 * todo - this needs to be split from XIVAPI due to its dependency on ApiRequest
 *
 * FOR XIVAPI ONRY
 *
 * Interact with Google Analytics
 * Guide: https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 *
 * Route tracking needs to be done manually to avoid capturing PID URLs or
 * any URL that contains any form of ID (eg Dev Apps)
 */
class GoogleAnalytics
{
    const ENDPOINT = 'https://www.google-analytics.com/collect';
    const VERIFY   = false;
    const VERSION  = 1;
    const TIMEOUT  = 5;

    /**
     * @param array $options
     */
    public static function query(array $options)
    {
        $postdata = http_build_query($options);
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            ]
        ];
    
        $context = stream_context_create($opts);

        file_get_contents(self::ENDPOINT, false, $context);
    }
    
    /**
     * Post a hit to Google Analytics
     */
    public static function hit($account, string $url): void
    {
        self::query([
            't'   => 'pageview',
            'v'   => self::VERSION,
            'cid' => ApiRequest::$idUnique,
            'z'   => mt_rand(0, 999999),
            'tid' => self::getTrackingId($account),
            'dp'  => $url,
        ]);
    }

    /**
     * Record an event
     */
    public static function event(string $account, string $category, string $action, string $label = '', int $value = 1): void
    {
        self::query([
            't'   => 'event',
            'v'   => self::VERSION,
            'cid' => ApiRequest::$idUnique,
            'z'   => mt_rand(0, 999999),
            'tid' => self::getTrackingId($account),
            'ec'  => $category,
            'ea'  => $action,
            'el'  => $label,
            'ev'  => $value,
        ]);
    }
    
    /**
     * Get tracking ID from provided account
     */
    private static function getTrackingId($account)
    {
        return str_ireplace('xivapi', getenv('SITE_CONFIG_GOOGLE_ANALYTICS'), $account);
    }

    public static function trackHits(string $url)
    {
        self::hit('xivapi', $url);
    }

    public static function trackBaseEndpoint(string $endpoint)
    {
        self::event('xivapi', 'Requests', 'Endpoint', $endpoint);
    }

    public static function trackLanguage()
    {
        self::event('xivapi', 'Requests', 'Language', Language::current());
    }
    
    public static function trackApiKey(string $apiKey, string $endpoint = null)
    {
        self::event('xivapi', 'Users', 'API Key', $apiKey);
        self::event('xivapi', 'Key Endpoints', $apiKey, $endpoint);
    }

    public static function trackApiKeyHardCapped(string $apiKey)
    {
        self::event('xivapi', 'Users', 'API Key Hard Capped', $apiKey);
    }
    
    public static function companionTrackItemAsUrl(string $itemId)
    {
        self::query([
            't'   => 'pageview',
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'z'   => mt_rand(0, 999999),
            'tid' => getenv('SITE_CONFIG_GOOGLE_ANALYTICS'),
            'dp'  => '/'. $itemId,
        ]);
    }
}
