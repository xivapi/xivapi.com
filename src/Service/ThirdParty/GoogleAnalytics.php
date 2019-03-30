<?php

namespace App\Service\ThirdParty;

use App\Entity\User;
use App\Service\API\ApiRequest;
use App\Service\Common\Language;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;

/**
 * Interact with Google Analytics
 * Guide: https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 *
 * Route tracking needs to be done manually to avoid capturing PID URLs or
 * any URL that contains any form of ID (eg Dev Apps)
 */
class GoogleAnalytics
{
    const ENDPOINT = 'http://www.google-analytics.com';
    const VERIFY   = false;
    const VERSION  = 1;
    const TIMEOUT  = 5;

    /**
     * @param array $options
     */
    public static function query(array $options)
    {
        try {
           $client = new Client([
                'base_uri' => self::ENDPOINT,
                'timeout'  => self::TIMEOUT,
                'verify'   => self::VERIFY,
            ]);
            
            $client->post('/collect', [
                RequestOptions::QUERY => $options
            ]);
        } catch (\Exception $ex) {
            // ignore
        }
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
    
    public static function trackApiKey(string $apiKey)
    {
        self::event('xivapi', 'Users', 'API Key', $apiKey);
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

    public static function lodestoneTrackContentAsUrl(string $lodestoneQueue)
    {
        self::query([
            't'   => 'pageview',
            'v'   => self::VERSION,
            'cid' => Uuid::uuid4()->toString(),
            'z'   => mt_rand(0, 999999),
            'tid' => 'UA-125096878-9',
            'dp'  => '/'. $lodestoneQueue,
        ]);
    }
}
