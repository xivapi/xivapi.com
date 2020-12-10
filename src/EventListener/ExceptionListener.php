<?php

namespace App\EventListener;

use App\Common\Exceptions\ApiUnknownPrivateKeyException;
use App\Common\Exceptions\BasicException;
use App\Common\Exceptions\CompanionMarketServerException;
use App\Common\Exceptions\SearchException;
use App\Exception\ApiPermaBanException;
use App\Exception\ApiRateLimitException;
use App\Exception\ApiTempBanException;
use App\Exception\ContentGoneException;
use App\Exception\InvalidCompanionMarketRequestException;
use App\Exception\LodestoneResponseErrorException;
use Lodestone\Exceptions\GenericException;
use Lodestone\Exceptions\LodestoneMaintenanceException;
use Lodestone\Exceptions\LodestoneNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Common\Constants\DiscordConstants;
use App\Common\Service\Redis\Redis;
use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\Utils\Environment;
use App\Common\Utils\Random;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }
    
    /**
     * Handle custom exceptions
     * @param GetResponseForExceptionEvent $event
     * @return null|void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $ex = $event->getException();
        
        // if config enabled to show errors and app env is prod.
        if (getenv('SITE_CONFIG_SHOW_ERRORS') == '1' && getenv('APP_ENV') == 'prod') {
            print_r([
                "#{$ex->getLine()} {$ex->getFile()}",
                $ex->getMessage(),
                $event->getException()->getTraceAsString()
            ]);
        }
        
        // if we're showing errors, don't handle them (eg in dev mode)
        if (getenv('SITE_CONFIG_SHOW_ERRORS') == '1') {
            return null;
        }
        
        $path = $event->getRequest()->getPathInfo();
        $pi   = pathinfo($path);
        
        // if it's an image
        if (isset($pi['extension']) && strlen($pi['extension']) > 2) {
            $event->setResponse(new Response("File not found: ". $path, 404));
            return null;
        }
        
        // remove root directories
        $file = str_ireplace('/home/dalamud/', '', $ex->getFile());
        $message = $ex->getMessage() ?: '(no-exception-message)';
        
        $code = $ex->getCode();
        $code = method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : $code;
        $code = (int)$code > 0 ? (int)$code : 500;
        
        $json = (Object)[
            'Error'   => true,
            'Subject' => 'XIVAPI ERROR',
            'Note'    => "Get on discord: https://discord.gg/MFFVHWC",
            'Message' => $message,
            'Hash'    => sha1($message),
            'Ex'      => get_class($ex),
            'ExCode'  => $code,
            'Url'     => $event->getRequest()->getBasePath(),
            'Debug'   => (Object)[
                'ID'      => Random::randomHumanUniqueCode() . date('ymdh'),
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'Code'    => $code,
                'Date'    => date('Y-m-d H:i:s'),
                'Env'     => defined(Environment::CONSTANT) ? constant(Environment::CONSTANT) : 'Prod(Assumed)',
            ],
        ];
        
        /**
         * Send error to discord if not sent within the hour AND the exception is not a valid one.
         */
        $validExceptions = [
            BasicException::class,
            UnauthorizedHttpException::class,
            NotAcceptableHttpException::class,
            NotFoundHttpException::class,
            LodestoneNotFoundException::class,
            ContentGoneException::class,
            CompanionMarketServerException::class,
            LodestoneMaintenanceException::class,
            GenericException::class,
            ApiRateLimitException::class,
            ApiUnknownPrivateKeyException::class,
            InvalidCompanionMarketRequestException::class,
            SearchException::class,
            ApiPermaBanException::class,
            ApiTempBanException::class,
            LodestoneResponseErrorException::class
        ];
        
        if (Redis::Cache()->get(__METHOD__ . $json->Hash) == null && !in_array($json->Ex, $validExceptions) && $json->Debug->Env != 'local') {
            Redis::Cache()->set(__METHOD__ . $json->Hash, true);
            Discord::mog()->sendMessage(
                DiscordConstants::ROOM_ERRORS,
                "```json\n". json_encode($json, JSON_PRETTY_PRINT) ."\n```"
            );
        }
        
        /**
         * Return a JSON error to user
         */
        $response = new JsonResponse($json, $code);
        $response->headers->set('Content-Type','application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->setStatusCode($code);
        
        $event->setResponse($response);
    }
}
