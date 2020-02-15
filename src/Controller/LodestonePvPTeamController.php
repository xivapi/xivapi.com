<?php

namespace App\Controller;

use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LodestonePvPTeamController extends AbstractController
{
    /**
     * @Route("/PvPTeam/Search")
     * @Route("/PvpTeam/Search")
     * @Route("/pvpteam/search")
     */
    public function search(Request $request)
    {
        if (empty(trim($request->get('name')))) {
            throw new NotAcceptableHttpException('You must provide a name to search.');
        }
        
        return $this->json(
            (new Api())->pvpteam()->search(
                $request->get('name'),
                ucwords(strtolower($request->get('server'))),
                $request->get('page') ?: 1
            )
        );
    }
    
    /**
     * @Route("/PvPTeam/{lodestoneId}")
     * @Route("/PvpTeam/{lodestoneId}")
     * @Route("/pvpteam/{lodestoneId}")
     */
    public function index($lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
    
        // initialise api
        $api = new Api();
        
        $response = (Object)[
            'PvPTeam' => $api->pvpteam()->get($lodestoneId),
        ];
    
        $response->PvPTeam->ID = $lodestoneId;
  
        return $this->json($response);
    }
}
