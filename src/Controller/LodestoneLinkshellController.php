<?php

namespace App\Controller;

use App\Exception\ContentGoneException;
use App\Service\Japan\Japan;
use App\Service\Lodestone\LinkshellService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\LinkshellQueue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class LodestoneLinkshellController extends Controller
{
    /** @var LinkshellService */
    private $service;
    
    public function __construct(LinkshellService $service)
    {
        $this->service = $service;
    }
    
    /**
     * todo - temp
     * @Route("/linkshell/{lodestoneId}/add")
     */
    public function add($lodestoneId)
    {
        LinkshellQueue::request($lodestoneId, 'linkshell_add');
        return $this->json(1);
    }
    
    /**
     * @Route("/Linkshell/Search")
     * @Route("/linkshell/search")
     */
    public function search(Request $request)
    {
        return $this->json(
            Japan::query('/japan/search/linkshell', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}")
     * @Route("/linkshell/{lodestoneId}")
     */
    public function index($lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
        
        $response = (Object)[
            'Linkshell'     => null,
            'Info' => (Object)[
                'Linkshell' => null,
            ],
        ];

        $linkshell = $this->service->get($lodestoneId);
        $response->Linkshell = $linkshell->data;
        $response->Info->Linkshell = [
            'State'     => $linkshell->ent->getState(),
            'Updated'   => $linkshell->ent->getUpdated()
        ];
    
        return $this->json($response);
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}/Update")
     * @Route("/linkshell/{lodestoneId}/update")
     */
    public function update($lodestoneId)
    {
        $linkshell = $this->service->get($lodestoneId);
    
        if ($linkshell->ent->isBlackListed()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Blacklisted');
        }
    
        if ($linkshell->ent->isAdding()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Not Added');
        }
    
        if ($this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }
        
        LinkshellQueue::request($lodestoneId, 'linkshell_update');

        $this->service->cache->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
