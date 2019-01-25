<?php

namespace App\Service\Apps;

use App\Controller\CompanionMarketController;
use App\Controller\TooltipsController;
use App\Controller\LodestoneCharacterController;
use App\Controller\LodestoneController;
use App\Controller\LodestoneFreeCompanyController;
use App\Controller\LodestonePvPTeamController;
use App\Controller\LodestoneStatisticsController;
use App\Controller\SearchController;
use App\Controller\XivGameContentController;
use App\Entity\UserApp;
use App\Entity\User;
use App\Exception\ApiUserBannedException;
use App\Exception\ApiRateLimitException;
use App\Exception\ApiRestrictedException;
use App\Exception\ApiAppBannedException;
use App\Service\Common\Language;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use Symfony\Component\HttpFoundation\Request;

class AppRequest
{
    /**
     * List of controllers that require a API Key
     */
    const URL = [
        CompanionMarketController::class,
        TooltipsController::class,
        LodestoneCharacterController::class,
        LodestoneController::class,
        LodestoneFreeCompanyController::class,
        LodestonePvPTeamController::class,
        LodestoneStatisticsController::class,
        SearchController::class,
        XivGameContentController::class
    ];

    /** @var AppManager */
    private static $manager = null;
    /** @var User */
    private static $user = null;
    /** @var UserApp */
    private static $app = null;

    /**
     * @param AppManager $manager
     */
    public static function setManager(AppManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * @param User $user
     */
    public static function setUser(?User $user = null): void
    {
        self::$user = $user;
    }

    /**
     * Get the current registered app
     *
     * @return UserApp|null
     */
    private static function app(): ?UserApp
    {
        $app = self::$app;

        // Ignore if no app is provided
        if ($app == false) {
            return null;
        }

        return $app;
    }
    
    /**
     * Get the current user
     *
     * @return User|null
     */
    private static function user(): ?User
    {
        $user = self::$user;
        
        // Ignore if no user is provided
        if ($user == false) {
            return null;
        }
        
        return $user;
    }

    /**
     * Register an application
     */
    public static function handleAppRequestRegistration(Request $request): void
    {
        /** @var UserApp $app */
        $app = self::$manager->getByKey($request->get('key') ?: null);
        self::$app = $app;
        
        // if user is logged in, skip controller check
        if (self::$app == null && self::$user) {
            return;
        }

        // grab controller related to this API request
        $controller = explode('::', $request->attributes->get('_controller'))[0];
        
        // check if app can access this endpoint
        if (in_array($controller, self::URL)) {
            if (empty($request->get('key')) || $app == null) {
                throw new ApiRestrictedException();
            }
        }

        // Do nothing if no app has been found (likely not using API)
        if ($app == false) {
            return;
        }

        // check if user is banned
        if ($app->getUser()->isBanned()) {
            GoogleAnalytics::trackUserBanned($app->getUser());
            throw new ApiUserBannedException();
        }
        
        // check if app is suspended
        if ($app->isBanned()) {
            GoogleAnalytics::trackAppBanned($app);
            throw new ApiAppBannedException();
        }

        // record auto ban count
        Redis::Cache()->increment('app_autoban_count_'. $app->getApiKey());
        Redis::Cache()->increment('app_autolimit_count_'. $app->getApiKey());

        // Track Developer App on Google Analytics (this is for XIVAPI Analytics)
        GoogleAnalytics::trackAppUsage($app);
        GoogleAnalytics::trackAppRouteAccess($app, $request);

        // If the app has Google Analytics, send a hit request.
        if ($googleAnalyticsId = $app->getGoogleAnalyticsId()) {
            GoogleAnalytics::hit($googleAnalyticsId, $request->getPathInfo());
        }

        // handle app rate limit
        self::handleAppRateLimit($request);

        // handle custom app tracking
        self::handleAppTracking($request);
    }

    /**
     * handle app tracking
     */
    private static function handleAppTracking(Request $request)
    {
        $app  = self::app();

        if ($app && $app->getGoogleAnalyticsId()) {
            $id = $app->getGoogleAnalyticsId();

            // custom events
            GoogleAnalytics::event($id, 'Requests', 'Endpoint', explode('/', $request->getPathInfo())[1] ?? 'Home');
            GoogleAnalytics::event($id, 'Requests', 'Language', Language::current());
        }
    }

    /**
     * Handle an apps rate limit
     */
    public static function handleAppRateLimit(Request $request)
    {
        // don't rate limit stuff not in our url list
        $controller = explode('::', $request->attributes->get('_controller'))[0];
        if (!in_array($controller, self::URL)) {
            return;
        }
        
        $ip   = md5($request->getClientIp());
        $user = self::user();
        $app  = self::app();

        $second = date('s');

        // default to no rate limit
        $limit = 1;

        // key is set on if an app exists, otherwise if a user exists, otherwise nout.
        $key = $app ? "app_rate_limit_ip_{$ip}_{$app->getApiKey()}_{$second}" : (
              $user ? "app_rate_limit_ip_{$ip}_{$user->getId()}_{$second}" : null
        );

        $keyBurst = $app ? "app_rate_limit_ip_{$ip}_{$app->getApiKey()}_burst" : (
                    $user ? "app_rate_limit_ip_{$ip}_{$user->getId()}_burst" : null
        );

        // if no key set, skip
        if ($key === null) {
            return;
        }
    
        // increment req counts
        $count = Redis::Cache()->get($key);
        $count = $count ? $count + 1 : 1;
        Redis::Cache()->set($key, $count, 2);

        $burst = null;
        if ($app) {
            // check if burst hit
            $burst = Redis::Cache()->get($keyBurst);

            // append on burst limit depending on if they've hit the burst or not.
            $limit = $app->getApiRateLimit() + ($burst ? 0 : $app->getApiRateLimitBurst());
        }

        // check limit against this ip
        if ($count > $limit) {
            // if not already marked as burst, do so
            if ($app && $burst == false) {
                Redis::Cache()->set($keyBurst, true, 5);
            }

            if ($app) {
                // record rate limit on XIVAPI
                GoogleAnalytics::event('{XIVAPI}', 'RateLimited', $app->getApiKey(), "{$app->getName()} - {$app->getUser()->getUsername()}");

                // if user has google analytics, fire off rate limit exception to user.
                if ($app->getGoogleAnalyticsId()) {
                    GoogleAnalytics::event($app->getGoogleAnalyticsId(), 'Exceptions', 'ApiRateLimitException', $ip);
                }
            }

            throw new ApiRateLimitException($count, $limit);
        }
    }

    /**
     * Handle an API exception
     */
    public static function handleException(\stdClass $json)
    {
        if ($app = self::app()) {
            // if the app has Google Analytics, send an event
            if ($id = $app->getGoogleAnalyticsId()) {
                GoogleAnalytics::event($id, 'Exceptions', 'ApiServiceErrorException', $json->Message);
                GoogleAnalytics::event($id, 'Exceptions', 'ApiServiceCodeException', $json->Debug->Code);
            }
        }
    }
}
