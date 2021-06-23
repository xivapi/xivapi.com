<?php

namespace App\Controller;

use App\Common\Service\Redis\Redis;
use App\Exception\LodestoneResponseErrorException;
use App\Service\API\ApiPermissions;
use App\Service\Content\LodestoneCharacter;
use App\Service\LodestoneQueue\CharacterConverter;
use Lodestone\Api;
use Lodestone\Entity\Character\ClassJob;
use Lodestone\Exceptions\LodestoneNotFoundException;
use Lodestone\Exceptions\LodestonePrivateException;
use Lodestone\Game\ClassJobs;
use Lodestone\Http\AsyncHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
                ucwords(strtolower($request->get('server'))),
                $request->get('page') ?: 1
            )
        );
    }

    /**
     * @Route("/Characters")
     * @Route("/characters")
     * @throws \Exception
     */
    public function multi(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        if (count($ids) > 200) {
            throw new \Exception("Woah there calm down, 200+ characters wtf?");
        }

        $response = [];
        foreach ($ids as $id) {
            $response[] = $this->index($request, $id, true);

            // sleep for .3s
            usleep(500000);
        }

        return $this->json($response);
    }

    /**
     * @Route("/Character/{lodestoneId}")
     * @Route("/character/{lodestoneId}")
     * @throws \Exception
     */
    public function index(Request $request, $lodestoneId, $internal = false)
    {
        $lodestoneId = (int)strtolower(trim($lodestoneId));

        // initialise api
        $api = new Api();

        // choose which content you want
        $data       = $request->get('data') ? explode(',', strtoupper($request->get('data'))) : [];
        $isExtended = $request->get('extended');
        $content    = (object)[
            'AC'   => in_array('AC', $data),
            'FR'   => in_array('FR', $data),
            'FC'   => in_array('FC', $data),
            'FCM'  => in_array('FCM', $data),
            'PVP'  => in_array('PVP', $data),
            'MIMO' => in_array('MIMO', $data),
            'CJ'   => in_array('CJ', $data)
        ];

        // -------------------------------------------
        // Mandatory
        // -------------------------------------------

        $rediskey = "lodestone_json_response_v6_" . $lodestoneId;
        $response = Redis::Cache()->get($rediskey, true);

        if (!$response || in_array('Character.Bio', $request->get('columns'))) {
            $api->config()->useAsync();

            $api->requestId('profile')->character()->get($lodestoneId);
            $api->requestId('classjobs')->character()->classjobs($lodestoneId);

            $lsdata = $api->http()->settle();

            AsyncHandler::$requestId = null;
            $api->config()->useSync();

            if ($lsdata['profile']->StatusCode ?? 0 > 0) {
                if ($lsdata['profile']->StatusCode == 404) {
                    throw new NotFoundHttpException('Character not found on Lodestone');
                } else {
                    $error = sprintf(LodestoneResponseErrorException::MESSAGE, $lsdata['profile']->StatusCode);
                    throw new LodestoneResponseErrorException($error);
                }
            }

            // response model
            $response = (object)[
                'Character'          => $lsdata['profile'],
                'Minions'            => null,
                'Mounts'             => null,

                // optional
                'Achievements'       => null,
                'AchievementsPublic' => null,
                'Friends'            => null,
                'FriendsPublic'      => null,
                'FreeCompany'        => null,
                'FreeCompanyMembers' => null,
                'PvPTeam'            => null,
            ];


            try {
                $classjobs = $lsdata['classjobs'];

                // set some root data
                $response->Character->ClassJobs          = $classjobs['classjobs'] ?? null;
                $response->Character->ClassJobsElemental = $classjobs['elemental'] ?? null;
                $response->Character->ClassJobsBozjan    = $classjobs['bozjan'] ?? null;

                // ---------------------- ACTIVE CLASS JOB ----------------------
                // look at this shit, pulled straight from lodestone parser :D
                // thanks SE
                $item = $response->Character->GearSet['Gear']['MainHand'];
                $name = explode("'", $item->Category)[0];

                // get class job id from the main-hand category name
                $gd = ClassJobs::findGameData($name);

                /** @var ClassJob $cj */
                foreach ($response->Character->ClassJobs as $cj) {
                    if ($cj->JobID === $gd->JobID) {
                        $response->Character->ActiveClassJob = clone $cj;
                        break;
                    }
                }
            } catch (\Exception $e) {
                $response->Character->ClassJobs = [];
                $response->Character->ActiveClassJob = null;
            }

            Redis::cache()->set($rediskey, $response, 300, true);
        } else {
            $response = (object)$response;
        }

        // ---------------------------------
        // Optional
        // ---------------------------------

        // fc id + pvp team id
        $fcId  = $response->Character->FreeCompanyId;
        $pvpId = $response->Character->PvPTeamId;

        // ensure bio is UT8
        $response->Character->Bio = mb_convert_encoding($response->Character->Bio, 'UTF-8', 'UTF-8');

        // Minions and mounts
        if ($content->MIMO) {
            // Two blocks here so one request cannot cancel the other.
            try {
                $response->Minions = $api->character()->minions($lodestoneId);
            } catch (LodestoneNotFoundException $e) {
                // If we get a 404, there's no minions, we can safely skip
            }
            try {
                $response->Mounts = $api->character()->mounts($lodestoneId);
            } catch (LodestoneNotFoundException $e) {
                // If we get a 404, there's no mounts, we can safely skip
            }
        }

        // Achievements
        if ($content->AC) {
            $achievements       = [];
            $achievementsPublic = true;

            try {
                // achievements might be private/public, can check on 1st one
                $first = $api->character()->achievements($lodestoneId, 1);
            } catch (LodestonePrivateException $ex) {
                // we catch this exception as users will probably still want to handle the response (profile, other data)
                // even if achievements are private
                $achievementsPublic = false;
            }

            // add public status to response
            $response->AchievementsPublic = $achievementsPublic;

            if ($achievementsPublic && $first) {
                $achievements = array_merge($achievements, $first->Achievements);

                $api->config()->useAsync();

                try {
                    // parse the rest of the pages
                    foreach ([2, 3, 4, 5, 6, 8, 11, 12] as $kindId) {
                        $api->config()->setRequestId("kind_{$kindId}");
                        $api->character()->achievements($lodestoneId, $kindId);
                    }

                    foreach ($api->http()->settle() as $res) {
                        if (isset($res->Error)) {
                            continue;
                        }

                        $achievements = array_merge($achievements, $res->Achievements);
                    }
                } catch (\Exception $ex) {
                    // ignore errors
                }

                $api->config()->useSync();

                // Attempt for category 13
                try {
                    $cat13 = $api->character()->achievements($lodestoneId, 13);
                    $achievements = array_merge($achievements, $cat13->Achievements);
                } catch (\Exception $ex) {
                    // ignore, might not have it
                }
            }

            $response->Achievements = (object)[
                'List'   => [],
                'Points' => 0
            ];

            // simplify achievements
            foreach ($achievements as $i => $achi) {
                $response->Achievements->Points   += $achi->Points;
                $response->Achievements->List[$i] = (object)[
                    'ID'   => $achi->ID,
                    'Date' => $achi->ObtainedTimestamp
                ];
            }

            if ($isExtended) {
                LodestoneCharacter::extendAchievementData($response->Achievements);
            }
        }

        // Friends
        if ($content->FR) {
            $friends       = [];
            $friendsPublic = true;

            // grab 1st page, so we know if there is more than 1 page
            try {
                $first   = $api->character()->friends($lodestoneId, 1);
                $friends = $first ? array_merge($friends, $first->Results) : $friends;
            } catch (LodestonePrivateException $ex) {
                // we catch this exception as users will probably still want to handle the response (profile, other data)
                // even if achievements are private
                $friendsPublic = false;
            }

            // add public status to response
            $response->FriendsPublic = $achievementsPublic;

            if ($friendsPublic && $first && $first->Pagination->PageTotal > 1) {
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
        if ($content->FC && $fcId) {
            $response->FreeCompany = $api->freecompany()->get($fcId);
        }

        // Free Company Members
        if ($content->FCM && $fcId) {
            $members = [];

            // grab 1st page, so we know if there is more than 1 page
            $first   = $api->freecompany()->members($response->Character->FreeCompanyId, 1);
            $members = $first ? array_merge($members, $first->Results) : $members;

            if ($first && $first->Pagination->PageTotal > 1) {
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
        if ($content->PVP && $pvpId) {
            $response->PvPTeam = $api->pvpteam()->get($pvpId);
        }

        // ---------------------------------
        // Finish up (data cleaning, converting, etc)
        // ---------------------------------

        // convert some shit
        CharacterConverter::handle($response->Character);

        if ($isExtended) {
            LodestoneCharacter::extendCharacterData($response->Character);
        }

        // ensure IDs exist
        $response->Character->ID = $lodestoneId;

        if ($response->FreeCompany && $response->Character->FreeCompanyId) {
            $response->FreeCompany->ID = $response->Character->FreeCompanyId;
        }


        if ($response->PvPTeam && $response->Character->PvPTeamId) {
            $response->PvPTeam->ID = (string)$response->Character->PvPTeamId;
        }

        if ($internal) {
            return $response;
        }

        return $this->json($response);
    }
}
