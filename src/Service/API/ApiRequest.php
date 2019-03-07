<?php

namespace App\Service\API;

use App\Controller\CompanionMarketController;
use App\Controller\TooltipsController;
use App\Controller\LodestoneCharacterController;
use App\Controller\LodestoneController;
use App\Controller\LodestoneFreeCompanyController;
use App\Controller\LodestonePvPTeamController;
use App\Controller\LodestoneStatisticsController;
use App\Controller\SearchController;
use App\Controller\XivGameContentController;
use App\Entity\User;
use App\Exception\ApiAppBannedException;
use App\Exception\ApiRateLimitException;
use App\Exception\ApiRestrictedException;
use App\Exception\ApiUnauthorizedAccessException;
use App\Service\Common\Language;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use App\Service\User\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * todo - exceptions in this class should be unique
 */
class ApiRequest
{
    /**
     * List of controllers that require a API Key
     */
    const API_CONTROLLERS = [
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

    /**
     * @var Users
     */
    private $users;
    /**
     * The current active user for this request
     * @var User
     */
    private $user;
    /**
     * @var Request
     */
    private $request;

    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    /**
     * Handle an incoming API request, this will:
     * - Check the controller being requested
     * - Checks the API key exists
     * - Checks the user for the API key is legit
     * - Checks the user is not banned, has access, access hasn't been suspended and checks endpoint access
     * - Checks rate limit (and increments it)
     * - Send any analytics data
     * - Registers the users permissions
     */
    public function handle(Request $request)
    {
        $this->request = $request;

        // if this request is not against an API controller, we don't need to do anything.
        if ($this->isApiController() === false) {
            return;
        }

        $this->checkApiKeyExists();

        /** @var User $user */
        $this->user = $this->users->getUserByApiKey($this->request->get('key'));

        // checks
        $this->checkUserIsNotBanned();
        $this->checkApiAccessGranted();
        $this->checkApiAccessNotSuspended();
        $this->checkApiAccessToEndpoint();

        // rate limit
        $this->checkRateLimit();

        // send any Google Analytics data
        $this->sendAnalyticData();

        // register the users permissions
        ApiRequestPermissions::registerPermissions($this->user);
    }

    /**
     * Checks the request against valid API controllers
     */
    private function isApiController()
    {
        // check if app can access this endpoint
        if (!in_array($this->getRequestController(), self::API_CONTROLLERS)) {
            return false;
        }

        return true;
    }

    /**
     * A single account gets X number of requests per second, this is account wide so they
     * must proxy their own service to avoid others from stealing their API Key.
     *
     * Rate limits are for the individual user and are account wide.
     */
    private function checkRateLimit()
    {
        $key = "api_rate_limit_{$this->user->getId()}";

        // Increment the users request count
        $count = Redis::Cache()->get($key);
        $count = $count ? $count + 1 : 1;
        Redis::Cache()->set($key, $count, 2);

        if ($count > $this->user->getApiRateLimit()) {
            // Record the rate limit event on XIVAPI Google Analytics account.
            GoogleAnalytics::event('{XIVAPI}', 'RateLimited', $this->user->getUsername(), $this->getRequestEndpoint());

            throw new ApiRateLimitException();
        }
    }

    private function checkApiKeyExists()
    {
        if (empty($this->request->get('key'))) {
            throw new ApiRestrictedException();
        }
    }

    private function checkUserIsNotBanned()
    {
        if ($this->user->isBanned()) {
            throw new ApiAppBannedException();
        }
    }

    private function checkApiAccessGranted()
    {
        if ($this->user->isApiEndpointAccessGranted() === false) {
            throw new ApiUnauthorizedAccessException();
        }
    }

    private function checkApiAccessNotSuspended()
    {
        if ($this->user->isApiEndpointAccessSuspended()) {
            throw new ApiUnauthorizedAccessException();
        }
    }

    private function checkApiAccessToEndpoint()
    {
        if (in_array($this->getRequestEndpoint(), $this->user->getApiEndpointAccess()) === false) {
            throw new ApiUnauthorizedAccessException();
        }
    }

    /**
     * Returns the controller of the current request
     */
    private function getRequestController()
    {
        return explode('::', $this->request->attributes->get('_controller'))[0];
    }

    /**
     * Returns the endpoint of the current request, eg: /character/1245 = character
     */
    private function getRequestEndpoint()
    {
        return strtolower(explode('/', $this->request->getPathInfo())[1]) ?? 'home';
    }

    private function sendAnalyticData()
    {
        // XIVAPI Google Analytics
        GoogleAnalytics::trackHits($this->request->getPathInfo());
        GoogleAnalytics::trackBaseEndpoint($this->getRequestEndpoint());
        GoogleAnalytics::trackLanguage();

        // check if user has an analytics key
        if (empty($this->user->getApiAnalyticsKey())) {
            return;
        }

        // User Google Analytics
        GoogleAnalytics::hit($this->user, $this->request->getPathInfo());
        GoogleAnalytics::event($this->user, 'Requests', 'Endpoint', $this->getRequestEndpoint());
        GoogleAnalytics::event($this->user, 'Requests', 'Language', Language::current());
    }
}
