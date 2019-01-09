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
    
        if (isset($pathinfo['extension'])) {
            $event->setResponse(new Response("File not found: ". $path, 404));
            return null;
        }
        
        $file = str_ireplace('/home/dalamud/dalamud', '', $ex->getFile());
        $file = str_ireplace('/home/dalamud/dalamud_staging', '', $file);
        $message = $ex->getMessage() ?: '(no-exception-message)';
        
        $json = [
            'Error'   => true,
            'Subject' => 'XIVAPI Service Error',
            'Message' => $message,
            'Debug'   => [
                'ID'      => Uuid::uuid4()->toString(),
                'Class'   => get_class($ex),
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'HasKey'  => $event->getRequest()->get('key') ? 'Yes' : 'No',
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'Code'    => method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : 500,
                'Time'    => time(),
                'Date'    => date('Y-m-d H:i:s'),
                'Note'    => "Get on discord: https://discord.gg/MFFVHWC and complain to @Vekien :)",
                'Env'     => constant(Environment::CONSTANT),
            ]
        ];
    
        file_put_contents(
            __DIR__.'/errors.txt',
            $message . " --- #{$ex->getLine()} {$file} \n",
            FILE_APPEND
        );

        $response = new JsonResponse($json, $json['Debug']['Code']);
        $response->headers->set('Content-Type','application/json');
        $response->headers->set('Access-Control-Allow-Origin','*');
        $event->setResponse($response);

        // log
        AppRequest::handleException($json);
    }
}
