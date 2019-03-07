<?php

namespace App\EventListener;

use App\Service\API\ApiRequest;
use App\Service\Common\Environment;
use App\Service\Common\Language;
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

        /** @var Request $request */
        $request = $event->getRequest();

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

        // record API access
        $this->apiRequest->handle($request);
    }
}
