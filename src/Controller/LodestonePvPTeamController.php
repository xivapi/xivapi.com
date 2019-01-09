<?php

namespace App\Controller;

use App\Exception\ContentGoneException;
use App\Service\Japan\Japan;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestonePvPTeamController extends Controller
{
    /** @var PvPTeamService */
    private $service;
    
    public function __construct(PvPTeamService $service)
    {
        $this->service = $service;
    }
    
    /**
     * todo - temp
     * @Route("/pvpteam/{lodestoneId}/add")
     */
    public function add($lodestoneId)
    {
        PvPTeamQueue::request($lodestoneId, 'pvp_team_add');
        return $this->json(1);
    }
    
    /**
     * @Route("/PvPTeam/Search")
     * @Route("/PvpTeam/Search")
     * @Route("/pvpteam/search")
     */
    public function search(Request $request)
    {
        return $this->json(
            Japan::query('/japan/search/pvpteam', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
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
        $response->Info->Linkshell = [
            'State'     => $pvp->ent->getState(),
            'Updated'   => $pvp->ent->getUpdated()
        ];
  
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
    
        if ($this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }

        PvPTeamQueue::request($lodestoneId, 'pvp_team_update');

        $this->service->cache->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
