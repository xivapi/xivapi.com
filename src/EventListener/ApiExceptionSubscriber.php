<?php

namespace App\EventListener;

use App\Service\Common\Environment;
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
        $ex   = $event->getException();
        
        if (getenv('SITE_CONFIG_SHOW_ERRORS') == '1' && getenv('APP_ENV') == 'prod') {
            print_r([
                "#{$ex->getLine()} {$ex->getFile()}",
                $ex->getMessage(),
                $event->getException()->getTraceAsString()
            ]);
        }
        
        if (getenv('SITE_CONFIG_SHOW_ERRORS') == '1') {
            return null;
        }
        
        
        $path = $event->getRequest()->getPathInfo();
        $pi   = pathinfo($path);
    
        if (isset($pi['extension']) && strlen($pi['extension'] > 2)) {
            $event->setResponse(new Response("File not found: ". $path, 404));
            return null;
        }
        
        $file = str_ireplace('/home/dalamud/dalamud', '', $ex->getFile());
        $file = str_ireplace('/home/dalamud/dalamud_staging', '', $file);
        $message = $ex->getMessage() ?: '(no-exception-message)';

        $json = (Object)[
            'Error'   => true,
            'Subject' => 'XIVAPI Service Error',
            'Message' => $message,
            'Hash'    => sha1($message),
            'Ex'      => get_class($ex),
            'Debug'   => (Object)[
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'Code'    => method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : 500,
                'Date'    => date('Y-m-d H:i:s'),
                'Note'    => "Get on discord: https://discord.gg/MFFVHWC and complain to @Vekien :) - Please remove your key from any url provided in the discord.",
                'Env'     => constant(Environment::CONSTANT),
            ]
        ];

        $response = new JsonResponse($json, $json->Debug->Code);
        $response->headers->set('Content-Type','application/json');
        $response->headers->set('Access-Control-Allow-Origin','*');
        $event->setResponse($response);
    }
}
