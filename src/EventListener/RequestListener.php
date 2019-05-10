<?php

namespace App\EventListener;

use App\Service\API\ApiRequest;
use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\Redis\Redis;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var ApiRequest */
    private $apiRequest;

    public function __construct(ApiRequest $apiRequest)
    {
        $this->apiRequest = $apiRequest;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
    
        if ($sentry = getenv('SENTRY')) {
            (new \Raven_Client($sentry))->install();
        }

        /** @var Request $request */
        $request = $event->getRequest();
        
        // if options, skip
        if ($request->getMethod() == 'OPTIONS') {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: *");
            header("HTTP/1.1 200 OK");
            die(200);
        }
        
        // look for multiple ?'s
        if (substr_count(urldecode($request->getQueryString()), '?') > 0) {
            Redis::Cache()->increment('query_params_errors');
            $counter = (int)Redis::Cache()->getCount('query_params_errors');

            throw new \Exception("({$counter}) https://en.wikipedia.org/wiki/Query_string");
        }

        // Another quick hack to convert all queries into the request object
        if ($queries = $request->query->all()) {
            foreach ($queries as $key => $value) {
                $request->request->set(strtolower($key), $value);
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

        // record API access
        $this->apiRequest->handle($request);
    }
}
