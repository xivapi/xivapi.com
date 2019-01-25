<?php

namespace App\EventListener;

use App\Service\Apps\AppRequest;
use App\Service\Common\Environment;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Watch any kind of exception and decide if it needs to be handled via an API response
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (getenv('SITE_CONFIG_SHOW_ERRORS') == '1') {
            return null;
        }
        
        $ex         = $event->getException();
        $path       = $event->getRequest()->getPathInfo();
        $pathinfo   = pathinfo($path);
    
        if (isset($pathinfo['extension']) && strlen($pathinfo['extension'] > 2)) {
            $event->setResponse(new Response("File not found: ". $path, 404));
            return null;
        }
        
        $file = str_ireplace('/home/dalamud/dalamud', '', $ex->getFile());
        $file = str_ireplace('/home/dalamud/dalamud_staging', '', $file);
        $message = $ex->getMessage() ?: '(no-exception-message)';

        // ensure key is not posted
        $event->getRequest()->query->remove('key');
        $event->getRequest()->request->remove('key');

        // grab json body if one provided
        $json = json_decode($event->getRequest()->getContent(), true);
        unset($json['key']);

        $json = (Object)[
            'Error'   => true,
            'Subject' => 'XIVAPI Service Error',
            'Message' => $message,
            'Hash'    => sha1($message),
            'Debug'   => (Object)[
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'JSON'    => $json,
                'Code'    => method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : 500,
                'Date'    => date('Y-m-d H:i:s'),
                'Note'    => "Get on discord: https://discord.gg/MFFVHWC and complain to @Vekien :)",
                'Env'     => constant(Environment::CONSTANT),
            ]
        ];
        
        file_put_contents(
            __DIR__.'/../../exceptions.log',
            "[{$json->Debug->Date}] {$json->Hash} {$event->getRequest()->get('key')} {$json->Message}\n",
            FILE_APPEND
        );

        $response = new JsonResponse($json, $json->Debug->Code);
        $response->headers->set('Content-Type','application/json');
        $response->headers->set('Access-Control-Allow-Origin','*');
        $event->setResponse($response);

        // log
        AppRequest::handleException($json);
    }
}
