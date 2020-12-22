<?php

namespace App\EventListener;

use App\Common\Exceptions\BasicException;
use App\Common\Utils\Environment;
use App\Common\Utils\Language;
use App\Service\API\ApiRequest;
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
        /** @var Request $request */
        $request = $event->getRequest();
    
        // if options or LE test, skip
        if ($request->getMethod() == 'OPTIONS' || stripos($request->getUri(), '.well-known') !== false) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: *");
            header("HTTP/1.1 200 OK");
            die(200);
        }
        
        if (!defined('REQUEST_TIME')) {
            define('REQUEST_TIME', time());
        }
        
        if (!$event->isMasterRequest()) {
            return;
        }
    
        if ($sentry = getenv('SENTRY')) {
            (new \Raven_Client($sentry))->install();
        }
        
        // Another quick hack to convert all queries into the request object
        if ($queries = $request->query->all()) {
            foreach ($queries as $key => $value) {
                $request->request->set(strtolower($key), $value);
            }
        }
        
        $controller = explode('::', $event->getRequest()->attributes->get('_controller'))[0];
        
        // Quick hack to allow json body requests
        if ($controller != 'App\Controller\MappyController' && $json = $request->getContent()) {
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
