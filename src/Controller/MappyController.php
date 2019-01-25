<?php

namespace App\Controller;

use App\Entity\MapPosition;
use App\Entity\User;
use App\Entity\MapCompletion;
use App\Exception\UnauthorizedAccessException;
use App\Repository\MapPositionRepository;
use App\Service\Maps\Mappy;
use App\Service\Redis\Redis;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MappyController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var UserService */
    private $userService;
    /** @var Mappy */
    private $mappy;
    
    public function __construct(EntityManagerInterface $em, UserService $userService, Mappy $mappy)
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->mappy = $mappy;
    }
    

    /**
     * @Route("/mappy", name="app_manage_map")
     */
    public function mappy(Request $request)
    {
        /** @var User $user */
        $user = $this->userService->getUser(true);
        $user->checkBannedStatusAndRedirectUserToDiscord();

        $regions        = [];
        $maps           = [];
        $mapsCompleted  = [];
        foreach (Redis::Cache()->get('ids_Map') as $id) {
            /** @var \stdClass $obj */
            $obj = Redis::Cache()->get("xiv_Map_{$id}");

            // ignore stuff with no placename
            if (!isset($obj->PlaceName->ID)) {
                continue;
            }

            /** @var MapPositionRepository $repo */
            $repo = $this->em->getRepository(MapPosition::class);
            $positions = $repo->getTotal($obj->ID);

            $map = [
                'ID'            => $obj->ID,
                'Url'           => $obj->Url,
                'MapFilename'   => $obj->MapFilename,
                'SizeFactor'    => $obj->SizeFactor,
                'Positions'     => $positions,
                'PlaceName'     => [
                    'ID'    => $obj->PlaceName->ID,
                    'Name'  => empty($obj->PlaceName->Name_en) ? 'Unknown' : $obj->PlaceName->Name_en,
                ],
                'PlaceNameSub'     => [
                    'ID'    => $obj->PlaceNameSub->ID ?? '-',
                    'Name'  => empty($obj->PlaceNameSub->Name_en) ? '' : $obj->PlaceNameSub->Name_en,
                ],
                'Region'        => [
                    'ID'    => $obj->PlaceNameRegion->ID ?? '-',
                    'Name'  => $obj->PlaceNameRegion->Name_en ?? 'No-Region',
                ],
                'Zone'          => [
                    'ID'    => $obj->TerritoryType->PlaceNameZone->ID ?? '-',
                    'Name'  => $obj->TerritoryType->PlaceNameZone->Name_en ?? 'No-Zone',
                ],
            ];

            $maps[$obj->PlaceNameRegion->ID ?? 'Unknown'][] = $map;
            $regions[$obj->PlaceNameRegion->ID ?? 'Unknown'] = $obj->PlaceNameRegion->Name_en;

            // get map state
            $repo = $this->em->getRepository(MapCompletion::class);
            $mapCompletion = $repo->findOneBy([ 'MapID' => $obj->ID ]);
            $mapsCompleted[$obj->ID] = false;

            /** @var MapCompletion $complete */
            if ($mapCompletion) {
                $mapsCompleted[$obj->ID] = $mapCompletion->isComplete();
            }

        }

        ksort($maps);
        ksort($regions);

        return $this->render('app/mappy.html.twig', [
            'allowed'           => true,
            'maps'              => $maps,
            'regions'           => $regions,
            'mapsCompleted'     => $mapsCompleted,
            'showCompleted'     => !empty($request->get('completed'))
        ]);
    }

    /**
     * @Route("/mappy/{map}", name="app_manage_map_view")
     */
    public function view(string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser(true);
        $user->checkBannedStatusAndRedirectUserToDiscord();

        $map = Redis::Cache()->get("xiv_Map_{$map}");

        // get completion info
        $repo = $this->em->getRepository(MapCompletion::class);
        $complete = $repo->findOneBy([ 'MapID' => $map->ID ]) ?: new MapCompletion();

        return $this->render('app/mappy_view.html.twig', [
            'allowed'   => true,
            'map'       => $map,
            'complete'  => $complete,
        ]);
    }

    /**
     * @Route("/mappy/{map}/data", name="app_manage_map_data")
     */
    public function data(Request $request, string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser(true);
        $user->checkBannedStatusAndRedirectUserToDiscord();

        $repo = $this->em->getRepository(MapPosition::class);
        $positions = [];

        /** @var MapPosition $pos */
        $offset = (int)($request->get('offset'))-2;
        $offset = $offset < 0 ? 0 : $offset;

        $size   = $request->get('size');
        foreach ($repo->findBy([ 'MapID' => $map ], [ 'Added' => 'Asc' ], $size, $offset) as $pos) {
            $positions[$pos->getID()] = [
                $pos->getName(),
                $pos->getType(),

                // divide by 2 as the map is half the size in the viewer
                $pos->getPixelX() / 2,
                $pos->getPixelY() / 2,

                $pos->getPosX(),
                $pos->getPosY()
            ];
        }

        return $this->json($positions);
    }

    /**
     * @Route("/mappy/{map}/update", name="app_manage_map_update")
     */
    public function update(Request $request, string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser(true);
        $user->checkBannedStatusAndRedirectUserToDiscord();

        $repo = $this->em->getRepository(MapCompletion::class);

        // get map completion or
        /** @var MapCompletion $complete */
        $complete = $repo->findOneBy([ 'MapID' => $map ]) ?: new MapCompletion();
        $complete
            ->setMapID($map)
            ->setNotes($request->get('notes'))
            ->setComplete($request->get('complete') == 'on');

        $this->em->persist($complete);
        $this->em->flush();

        return $this->redirectToRoute('app_manage_map_view', [
            'map' => $map
        ]);
    }

    /**
     * @Route("/mappy/verify")
     */
    public function verify(Request $request)
    {
        $code = $request->get('code');

        if (empty($code)) {
            throw new NotFoundHttpException();
        }

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([ 'mappyAccessCode' => $code ]);

        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        return $this->json($user->getId());
    }

    /**
     * @Route("/mappy/mark/complete")
     */
    public function markComplete(Request $request)
    {
        $repo = $this->em->getRepository(MapCompletion::class);
        $complete = $repo->findOneBy([ 'MapID' => $request->get('map') ]) ?: new MapCompletion();

        $complete
            ->setMapID($request->get('map'))
            ->setComplete(true)
            ->setNotes('Marked complete via the app');

        $this->em->persist($complete);
        $this->em->flush();

        return $this->json([
            'status' => 'complete'
        ]);
    }

    /**
     * @Route("/mappy/map/open")
     */
    public function openMap(request $request)
    {
        return $this->redirectToRoute('app_manage_map_view', [
            'map' => $request->get('map')
        ]);
    }

    /**
     * @Route("/mappy/submit")
     */
    public function submit(Request $request)
    {
        $json = json_decode($request->getContent());

        if ($request->getMethod() !== 'POST' || empty($json)) {
            throw new UnauthorizedAccessException();
        }

        return $this->json([
            'saved' => $this->mappy->save($json->data)
        ]);
    }

}
