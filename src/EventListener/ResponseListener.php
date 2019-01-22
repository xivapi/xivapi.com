<?php

namespace App\EventListener;

use App\Service\Common\Arrays;
use App\Service\Common\DataType;
use App\Service\Common\Language;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        /** @var JsonResponse $response */
        $response = $event->getResponse();
        /** @var Request $request */
        $request = $event->getRequest();

        // only process if response is a JsonResponse
        if (get_class($response) === JsonResponse::class) {
            // grab json
            $json = json_decode($response->getContent(), true);
            
            // ignore when it's an exception
            if (isset($json['Error']) && isset($json['Debug'])) {
                return;
            }
            
            // if printing a query, ignore any modifications
            if ($request->get('print_query')) {
                $response->setContent(
                    json_encode(
                        json_decode($response->getContent()), JSON_PRETTY_PRINT
                    )
                );
                return;
            }
            
            // last minute handlers
            if (is_array($json)) {
                //
                // Language
                //
                $json = Language::handle($json, $request->get('language'));

                //
                // Schema
                //
                // if its a list, handle columns per entry
                // ignored when schema is requested
                //
                if ($columns = $request->get('columns')) {
                    // get columns param
                    $columns = array_unique(explode(',', $columns));
                    
                    if (isset($json['Pagination']) && !empty($json['Results'])) {
                        foreach ($json['Results'] as $r => $result) {
                            $columns = Arrays::extractColumnsCount($result, $columns);
                            $columns = Arrays::extractMultiLanguageColumns($columns);
                            $json['Results'][$r] = Arrays::extractColumns($result, $columns);
                        }
                    } else if (!isset($json['Pagination'])) {
                        $columns = Arrays::extractColumnsCount($json, $columns);
                        $columns = Arrays::extractMultiLanguageColumns($columns);
                        $json    = Arrays::extractColumns($json, $columns);
                    }
                }

                //
                // Mini
                //
                if ($request->get('minify')) {
                    if (isset($json['Pagination']) && !empty($json['Results'])) {
                        foreach ($json['Results'] as $r => $result) {
                            $json['Results'][$r] = Arrays::minification($result);
                        }
                    } else if (!isset($json['Pagination'])) {
                        $json = Arrays::minification($json);
                    }
                }

                //
                // Ensure data types are enforced cleanly
                //
                $json = DataType::ensureStrictDataTypes($json);

                //
                // Sort data
                //
                $json = Arrays::sortArrayByKey($json);
            }

            //
            // Snake case check
            //
            if ($request->get('snake_case')) {
                Arrays::snakeCase($json);
            }

            //
            // Remove keys check
            //
            if ($request->get('remove_keys')) {
                Arrays::removeKeys($json);
            }

            // save
            $response->setContent(
                json_encode($json, JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION)
            );
            
            // if pretty printing
            if ($event->getRequest()->get('pretty')) {
                $response->setContent(
                    json_encode(
                        json_decode($response->getContent()), JSON_PRETTY_PRINT
                    )
                );
            }

            // work out expiry time
            switch ($request->attributes->get('_controller')) {
                default:
                    $expires = 5;
                    break;

                case 'App\Controller\XivGameContentController::patches':
                case 'App\Controller\XivGameContentController::servers':
                case 'App\Controller\XivGameContentController::serversByDataCenter':
                case 'App\Controller\XivGameContentController::content':
                case 'App\Controller\XivGameContentController::contentList':
                case 'App\Controller\XivGameContentController::schema':
                case 'App\Controller\XivGameContentController::contentData':
                case 'App\Controller\SearchController::search':
                case 'App\Controller\SearchController::searchMapping':
                case 'App\Controller\SearchController::lore':
                    $expires = 3600 * 4;
                    break;

                case 'App\Controller\CompanionMarketController::itemPrices':
                case 'App\Controller\CompanionMarketController::itemHistory':
                case 'App\Controller\CompanionMarketController::categoryList':
                case 'App\Controller\CompanionMarketController::categories':
                    $expires = 300;
                    break;
            }

            $response->setMaxAge($expires)->setExpires((new Carbon())->addSeconds($expires))->setPublic();

            $response->headers->set('Content-Type','application/json');
            $response->headers->set('Access-Control-Allow-Origin','*');
            $event->setResponse($response);
        }
    }
}
