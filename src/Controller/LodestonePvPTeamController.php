<?php

namespace App\Controller;

use App\Exception\ContentGoneException;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Lodestone\Api;
use App\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestonePvPTeamController extends AbstractController
{
    /** @var PvPTeamService */
    private $service;
    
    public function __construct(PvPTeamService $service)
    {
        $this->service = $service;
    }
    
    /**
     * @Route("/PvPTeam/Search")
     * @Route("/PvpTeam/Search")
     * @Route("/pvpteam/search")
     */
    public function search(Request $request)
    {
        return $this->json(
            (new Api())->searchPvPTeam(
                $request->get('name'),
                ucwords($request->get('server')),
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
        
        $response = (Object)[
            'PvPTeam' =>     null,
            'Info' => (Object)[
                'PvPTeam' => null,
            ],
        ];
    
        $pvp = $this->service->get($lodestoneId);
        $response->Linkshell = $pvp->data;
        $response->Info->Linkshell = $pvp->ent->getInfo();
  
        return $this->json($response);
    }

    /**
     * @Route("/PvPTeam/{lodestoneId}/Update")
     * @Route("/pvpteam/{lodestoneId}/update")
     */
    public function update($lodestoneId)
    {
        $pvp = $this->service->get($lodestoneId);
    
        if ($pvp->ent->isBlackListed()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Blacklisted');
        }
    
        if ($pvp->ent->isAdding()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Not Added');
        }
    
        if (Redis::Cache()->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }

        PvPTeamQueue::request($lodestoneId, 'pvp_team_update');

        Redis::Cache()->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
