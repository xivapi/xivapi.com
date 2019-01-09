<?php

namespace App\Controller;

use App\Entity\MapPosition;
use App\Service\ThirdParty\GoogleAnalytics;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Routes in here will eventually be deleted
 */
class DeprecatedController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * @Route("/debug/xivdb")
     */
    public function xivdbAnalytics(Request $request)
    {
        $url = trim($request->get('route'));
        
        if (empty($url)) {
            return $this->json(0);
        }
    
        GoogleAnalytics::event(
            'UA-125096878-4',
            'XIVDB',
            'Route',
            $url,
            1
        );
        
        return $this->json(1);
    }
    
    /**
     * @Route("/mapdata/{name}/{id}")
     */
    public function deprecatedMapData($name, $id)
    {
        $name = strtolower($name);

        $nameToField = [
            'map'       => 'MapID',
            'placename' => 'PlaceNameID',
            'territory' => 'MapTerritoryID',
        ];

        $field = $nameToField[$name] ?? false;
        if (!$field) {
            throw new \Exception('There is no map data for the content: '. $name);
        }

        $repo = $this->em->getRepository(MapPosition::class);
        $pos  = [];

        /** @var MapPosition $position */
        foreach ($repo->findBy([ $field => $id ], [ 'Added' => 'ASC' ]) as $position) {
            $pos[] = $position->toArray();
        }

        return $this->json($pos);
    }
}
