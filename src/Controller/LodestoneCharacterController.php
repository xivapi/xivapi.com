<?php

namespace App\Controller;

use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LodestoneCharacterController extends AbstractController
{
    /**
     * @Route("/Character/Search")
     * @Route("/character/search")
     */
    public function search(Request $request)
    {
        if (empty(trim($request->get('name')))) {
            throw new NotAcceptableHttpException('You must provide a name to search.');
        }

        return $this->json(
            (new Api())->character()->search(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page') ?: 1
            )
        );
    }

    /**
     * @Route("/Character/{lodestoneId}")
     * @Route("/character/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $lodestoneId = (int)strtolower(trim($lodestoneId));

        // initialise api
        $api = new Api();

        // choose which content you want
        $data = $request->get('data') ? explode(',', strtoupper($request->get('data'))) : [];
        $content = (object)[
            'AC'  => in_array('AC', $data),
            'FR'  => in_array('FR', $data),
            'FC'  => in_array('FC', $data),
            'FCM' => in_array('FCM', $data),
            'PVP' => in_array('PVP', $data),
        ];

        // response model
        $response = (Object)[
            'Character'          => $api->character()->get($lodestoneId),
            'Achievements'       => null,
            'Friends'            => null,
            'FreeCompany'        => null,
            'FreeCompanyMembers' => null,
            'PvPTeam'            => null,
        ];

        // Achievements
        if ($content->AC) {
            $api->config()->useAsync();

            $api->character()->achievements($lodestoneId, 1);
            $api->character()->achievements($lodestoneId, 2);
            $api->character()->achievements($lodestoneId, 3);
            $api->character()->achievements($lodestoneId, 4);
            $api->character()->achievements($lodestoneId, 5);
            $api->character()->achievements($lodestoneId, 6);
            $api->character()->achievements($lodestoneId, 8);
            $api->character()->achievements($lodestoneId, 11);
            $api->character()->achievements($lodestoneId, 12);
            $api->character()->achievements($lodestoneId, 13);

            $response->Achievements = $api->http()->settle();
            $api->config()->useSync();
        }

        // Friends
        if ($content->FR) {
            $api->config()->useAsync();

            $friends = [];

            // grab 1st page
            $friends[] = $api->character()->friends($lodestoneId);

            // parse rest of pages
            if ($friends[0]->Pagination->PageTotal > 1) {
                foreach (range(2, $friends[0]->Pagination->PageTotal) as $page) {
                    $friends[] = $api->character()->friends($lodestoneId, $page);
                }
            }

            $response->Friends = $friends;
            $api->config()->useSync();
        }

        // Free Company
        if ($content->FC) {
            $fcId = $response->Character->FreeCompanyId;
            $response->FreeCompany = $api->freecompany()->get($fcId);
        }

        // Free Company Members
        if ($content->FCM) {
            $fcId = $response->Character->FreeCompanyId;
            $api->config()->useAsync();

            $members = [];

            // grab 1st page
            $members[] = $api->freecompany()->members($lodestoneId);

            // parse rest of pages
            if ($members[0]->Pagination->PageTotal > 1) {
                foreach (range(2, $members[0]->Pagination->PageTotal) as $page) {
                    $members[] = $api->freecompany()->members($lodestoneId, $page);
                }
            }

            $response->FreeCompanyMembers = $members;
            $api->config()->useSync();
        }

        // PVP Team
        if ($content->PVP && isset($response->Character->PvPTeamId)) {
            $pvpId = $response->Character->PvPTeamId;
            $response->PvPTeam = $api->pvpteam()->get($pvpId);
        }

        return $this->json($response);
    }
}
