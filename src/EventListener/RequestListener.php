<?php

namespace App\EventListener;

use App\Service\Apps\AppManager;
use App\Service\Apps\AppRequest;
use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\ThirdParty\GoogleAnalytics;
use App\Service\ThirdParty\Sentry;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var UserService */
    private $userService;
    /** @var AppManager */
    private $appManager;

    public function __construct(AppManager $appManager, UserService $userService)
    {
        $this->appManager = $appManager;
        $this->userService = $userService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        /** @var Request $request */
        $request = $event->getRequest();

        // todo - temp measure until implement a proper blacklist.
        if ($request->get('key') == '0e1339f00eb14023a206afef') {
            die('API Key has been blacklisted from XIVAPI. Please update the app or extension you are using.');
        }

        // Another quick hack to convert all queries into the request object
        if ($queries = $request->query->all()) {
            foreach ($queries as $key => $value) {
                $request->request->set($key, $value);
            }
        }

        // Quick hack to allow json body requests
        if ($json = $request->getContent()) {
            if (trim($json[0]) === '{') {
                $json = \GuzzleHttp\json_decode($json);

                foreach($json as $key => $value) {
                    $request->request->set($key, is_array($value) ? implode(',', $value) : $value);
                }
            }
        }

        // register environment
        Environment::register($request);

        // register language based on domain
        Language::register($request);

        // register app keys
        AppRequest::setManager($this->appManager);
        AppRequest::setUser($this->userService->getUser());
        AppRequest::handleAppRequestRegistration($request);

        // record analytics
        GoogleAnalytics::trackHits($request);
        GoogleAnalytics::trackBaseEndpoint($request);
    }
}
