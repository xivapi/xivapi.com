<?php

namespace App\Service\ThirdParty;

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
}
