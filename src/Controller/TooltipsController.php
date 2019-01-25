<?php

namespace App\Controller;

use App\Service\Redis\Redis;
use App\Service\Tooltips\Tooltips;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TooltipsController extends Controller
{
    /**
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
                $content = Redis::Cache()->get("xiv_{$contentName}_{$id}");

                if (!$content) {
                    continue;
                }

                // build tooltip view
                $view = Tooltips::get($contentName, $content);

                // set response
                $response[$contentName][$id] = $view;
            }
        }

        return $this->json($response);
    }
}
