<?php

namespace App\Controller;

use App\Service\Redis\Cache;
use App\Service\Tooltips\Views;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 *
 * At this time, this class is deprecated.
 */
class ConceptTooltipsController extends Controller
{
    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @Route("/Tooltips", methods="POST")
     * @Route("/tooltips", methods="POST")
     */
    public function Tooltips(Request $request)
    {
        // decode request
        $json = json_decode($request->getContent());

        if (!$json) {
            return $this->json([
                'error' => 'No content data in JSON request'
            ]);
        }

        // build tooltip response
        $response = [];
        foreach ($json as $contentName => $ids) {
            foreach ($ids as $id) {
                // grab content
                $content = $this->cache->get("xiv_{$contentName}_{$id}");

                if (!$content) {
                    continue;
                }

                // build tooltip view
                $view = Views::get($contentName, $content);

                // set response
                $response[$contentName][$id] = $view;
            }
        }

        return $this->json($response);
    }
}
