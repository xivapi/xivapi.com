<?php

namespace App\Service\API;

use App\Controller\MappyController;
use App\Controller\MarketController;
use App\Controller\TooltipsController;
use App\Controller\LodestoneCharacterController;
use App\Controller\LodestoneController;
use App\Controller\LodestoneFreeCompanyController;
use App\Controller\LodestonePvPTeamController;
use App\Controller\SearchController;
use App\Controller\XivGameContentController;
use App\Entity\User;
use App\Exception\ApiAppBannedException;
use App\Exception\ApiRateLimitException;
use App\Exception\ApiUnauthorizedAccessException;
use App\Service\Common\Language;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use App\Service\User\Users;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * todo - exceptions in this class should be unique
 */
class ApiRequest
{
    const KEY_FIELD = 'private_key';
    const MAX_RATE_LIMIT_KEY = 20;
    const MAX_RATE_LIMIT_GLOBAL = 5;
    
    /**
     * List of controllers that require a API Key
     */
    const API_CONTROLLERS = [
        MarketController::class,
        MappyController::class,
        TooltipsController::class,
        LodestoneCharacterController::class,
        LodestoneController::class,
        LodestoneFreeCompanyController::class,
        LodestonePvPTeamController::class,
        SearchController::class,
        XivGameContentController::class
    ];

    /**
     * Static ID for requests, will either be a hashed ip or
     * a developer key (whatever is used to rate limit)
     */
    public static $idStatic;
    public static $idDynamic;
    public static $idUnique;

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
    /**
     * @var string
     */
    private $apikey;

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
        $this->apikey  = trim($this->request->get(self::KEY_FIELD));

        // set request ids
        $this->setApiRequestIds();

        // if this request is not against an API controller, we don't need to do anything.
        if ($this->isApiController() === false) {
            return;
        }
        
        // if no key, handle per ip
        if ($this->hasApiKey() === false) {
            $this->checkUserRateLimit();
            $this->sendUsageAnalyticData();
            return;
        }

        /** @var User $user */
        $this->user = $this->users->getUserByApiKey($this->apikey);
        $this->setApiPermissions();

        // checks
        $this->checkUserIsNotBanned();
        $this->checkApiAccessNotSuspended();

        // rate limit
        $this->checkDeveloperRateLimit();

        // send any developer Google Analytics data
        $this->sendUsageAnalyticData();
        $this->sendDeveloperAnalyticData();
    }

    /**
     * Set request ID's, which will either use Api Key or Client IP
     */
    private function setApiRequestIds()
    {
        $unique = (Object)[
            'static'  => sha1("ga_". ($this->apikey ?: $this->request->getClientIp())),
            'dynamic' => sha1("ga_". ($this->apikey ?: $this->request->getClientIp()) . time()),
            'unique'  => Uuid::uuid4()->toString(),
        ];

        ApiRequest::$idStatic  = $unique->static;
        ApiRequest::$idDynamic = $unique->dynamic;
        ApiRequest::$idUnique  = $unique->unique;
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
     * States if an API key exists in the request
     */
    private function hasApiKey(): bool
    {
        return !empty($this->request->get(self::KEY_FIELD));
    }

    /**
     * Sets the static permissions for the user if an account exists
     */
    private function setApiPermissions()
    {
        if ($this->user) {
            ApiPermissions::set($this->user->getApiPermissions());
        }
    }

    /**
     * A single account gets X number of requests per second, this is account wide so they
     * must proxy their own service to avoid others from stealing their API Key.
     *
     * Rate limits are for the individual user and are account wide.
     */
    private function checkDeveloperRateLimit()
    {
        $key = "api_rate_limit_user_{$this->user->getId()}";
        $this->handleRateLimit($key, $this->user->getApiRateLimit());
    }
    
    /**
     * Check a users rate limit
     */
    private function checkUserRateLimit()
    {
        $ip  = md5($this->request->getClientIp());
        $key = "api_rate_limit_client_{$ip}";
        
        $this->handleRateLimit($key);
    }
    
    /**
     * Handle rate limit tracking
     */
    private function handleRateLimit($key, $limit = self::MAX_RATE_LIMIT_GLOBAL)
    {
        // current and last second
        $key = $key .'_v3_'. (int)date('s');

        // increment
        $count = Redis::Cache()->get($key);
        $count++;
        Redis::Cache()->set($key, $count, 2);

        // throw exception if hit count too high
        if ($count > $limit) {
            throw new ApiRateLimitException();
        }
    }
    
    /**
     * Check that the user is not banned, if they provided a key
     */
    private function checkUserIsNotBanned()
    {
        if ($this->user->isBanned()) {
            throw new ApiAppBannedException();
        }
    }
    
    /**
     * Check that the API key has not been suspended
     */
    private function checkApiAccessNotSuspended()
    {
        if ($this->user->isApiEndpointAccessSuspended()) {
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
        return strtolower(explode('/', $this->request->getPathInfo())[1]) ?? 'x';
    }
    
    /**
     * Send xivapi usage analytic data
     */
    private function sendUsageAnalyticData()
    {
        // XIVAPI Google Analytics
        GoogleAnalytics::trackHits($this->request->getPathInfo());
        GoogleAnalytics::trackBaseEndpoint($this->getRequestEndpoint());
        GoogleAnalytics::trackApiKey($this->apikey ?: 'no_api_key');
        GoogleAnalytics::trackLanguage();
    }
    
    /**
     * Send developer specific analytic data
     */
    private function sendDeveloperAnalyticData()
    {
        $key = trim($this->user->getApiAnalyticsKey());

        // check if user has an analytics key
        if (empty($key)) {
            return;
        }

        file_put_contents(__DIR__ ."/ga.log", "{$key} {$this->request->getPathInfo()}\n");

        // User Google Analytics
        GoogleAnalytics::hit($key, $this->request->getPathInfo());
        GoogleAnalytics::event($key, 'Requests', 'Endpoint', $this->getRequestEndpoint());
        GoogleAnalytics::event($key, 'Requests', 'Language', Language::current());
    }
}
