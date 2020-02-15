<?php

namespace App\Controller;

use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class LodestoneLinkshellController extends AbstractController
{
    /**
     * @Route("/Linkshell/Search")
     * @Route("/linkshell/search")
     */
    public function search(Request $request)
    {
        if (empty(trim($request->get('name')))) {
            throw new NotAcceptableHttpException('You must provide a name to search.');
        }
        
        return $this->json(
            (new Api())->linkshell()->search(
                $request->get('name'),
                ucwords(strtolower($request->get('server'))),
                $request->get('page') ?: 1
            )
        );
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}")
     * @Route("/linkshell/{lodestoneId}")
     */
    public function index($lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
    
        // initialise api
        $api = new Api();
    
        $response = (Object)[
            'Linkshell' => null,
        ];

        $linkshell = $api->linkshell()->get($lodestoneId);
        $linkshell->ID = $lodestoneId;

        $members = $linkshell->Results;

        if ($linkshell && $linkshell->Pagination->PageTotal > 1) {
            // parse the rest of pages
            $api->config()->useAsync();
            foreach (range(2, $linkshell->Pagination->PageTotal) as $page) {
                $api->linkshell()->get($lodestoneId, $page);
            }

            foreach ($api->http()->settle() as $res) {
                $members = array_merge($members, $res->Results);
            }
            $api->config()->useSync();
        }

        $linkshell->Results = $members;

        // reset this sicne we're getting all pages in 1 go
        $linkshell->Pagination->Page = 1;
        $linkshell->Pagination->PageNext = 1;
        $linkshell->Pagination->PagePrev = null;
        $linkshell->Pagination->PageTotal = 1;
        $linkshell->Pagination->Results = count($members);
        $linkshell->Pagination->ResultsPerPage = count($members);

        $response->Linkshell = $linkshell;

        return $this->json($response);
    }
}
