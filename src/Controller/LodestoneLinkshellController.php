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
                ucwords($request->get('server')),
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
            'Linkshell' => $api->linkshell()->get($lodestoneId),
        ];
    
        $response->Linkshell->ID = $lodestoneId;
    
        return $this->json($response);
    }
}
