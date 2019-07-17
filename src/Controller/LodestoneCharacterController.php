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
            $achievements = [];

            // achievements might be private/public, can check on 1st one
            $first = $api->character()->achievements($lodestoneId, 1);

            if ($first) {
                $achievements = array_merge($achievements, $first->Achievements);

                // parse the rest of the pages
                $api->config()->useAsync();
                foreach([2,3,4,5,6,8,11,12,13] as $kindId) {
                    $api->config()->setRequestId("kind_{$kindId}");
                    $api->character()->achievements($lodestoneId, $kindId);
                }

                foreach ($api->http()->settle() as $res) {
                    $achievements = array_merge($achievements, $res->Achievements);
                }
                $api->config()->useSync();
            }

            $response->Achievements = $achievements;
        }

        // Friends
        if ($content->FR) {
            $friends = [];

            // grab 1st page, so we know if there is more than 1 page
            $first = $api->character()->friends($lodestoneId, 1);

            if ($first && $first->Pagination->PageTotal > 1) {
                $friends = array_merge($friends, $first->Results);

                // parse the rest of pages
                $api->config()->useAsync();
                foreach (range(2, $first->Pagination->PageTotal) as $page) {
                    $api->character()->friends($lodestoneId, $page);
                }

                foreach ($api->http()->settle() as $res) {
                    $friends = array_merge($friends, $res->Results);
                }
                $api->config()->useSync();
            }

            $response->Friends = $friends;
        }

        // Free Company
        if ($content->FC) {
            $fcId = $response->Character->FreeCompanyId;
            $response->FreeCompany = $api->freecompany()->get($fcId);
        }

        // Free Company Members
        if ($content->FCM) {
            $members = [];

            // grab 1st page, so we know if there is more than 1 page
            $first = $api->freecompany()->members($response->Character->FreeCompanyId, 1);

            if ($first && $first->Pagination->PageTotal > 1) {
                $members = array_merge($members, $first->Results);

                // parse the rest of pages
                $api->config()->useAsync();
                foreach (range(2, $first->Pagination->PageTotal) as $page) {
                    $api->freecompany()->members($response->Character->FreeCompanyId, $page);
                }

                foreach ($api->http()->settle() as $res) {
                    $members = array_merge($members, $res->Results);
                }
                $api->config()->useSync();
            }

            $response->FreeCompanyMembers = $members;
        }

        // PVP Team
        if ($content->PVP && isset($response->Character->PvPTeamId)) {
            $pvpId = $response->Character->PvPTeamId;
            $response->PvPTeam = $api->pvpteam()->get($pvpId);
        }

        return $this->json($response);
    }
}
