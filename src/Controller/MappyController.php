<?php

namespace App\Controller;

use App\Common\Entity\User;
use App\Service\API\ApiPermissions;
use App\Service\API\ApiRequest;
use App\Common\Utils\Arrays;
use App\Service\Maps\Mappy;
use App\Common\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class MappyController extends AbstractController
{
    /** @var Mappy */
    private $mappy;
    /** @var Users */
    private $users;

    public function __construct(Mappy $mappy, Users $users)
    {
        $this->mappy = $mappy;
        $this->users = $users;
    }

    /**
     * Confirms access to submitting data to XIVAPI
     *
     * @Route("/mappy/check-key", name="mappy_confirm")
     */
    public function check(Request $request)
    {
        // check if the user has permission
        if (ApiPermissions::has(ApiPermissions::PERMISSION_MAPPY)) {
            /** @var User $user */
            $user = $this->users->getUserByApiKey($request->get(ApiRequest::KEY_FIELD));

            return $this->json([
                'ok'   => true,
                'user' => $user->getUsername()
            ]);
        }

        return $this->json([
            'ok'   => false,
            'user' => null,
        ]);
    }

    /**
     * Gets data for an entire map inside Mappy and returns it as JSON
     *
     * @Route("/mappy/map/{mapId}", name="mappy_data_map", methods={"GET"})
     */
    public function getMap(int $mapId)
    {
        $entries = $this->mappy->getByMap($mapId);
        return $this->json($entries);
    }

    /**
     * Gets data for an entire map inside Mappy and returns it as JSON
     *
     * @Route("/mappy/updates", name="mappy_data_updates")
     */
    public function getUpdates()
    {
        $data = $this->mappy->getFullData();
        $updates = [];
        foreach ($data as $entry) {
            if (!isset($updates[$entry->getMapID()])) {
                $updates[$entry->getMapID()] = [];
            }

            if (!isset($updates[$entry->getMapID()][$entry->getType()]) || $updates[$entry->getMapID()][$entry->getType()] < $entry->getAdded()) {
                $updates[$entry->getMapID()][$entry->getType()] = $entry->getAdded();
            }
        }
        return $this->json($updates);
    }

    /**
     * Gets a list of the GatheringPoints that we have position for inside a given map
     *
     * @Route("/mappy/map/{mapId}/nodes", name="mappy_data_map_nodes")
     */
    public function getNodesForMap(int $mapId)
    {
        $entries = $this->mappy->getByMap($mapId);
        $nodes = [];
        foreach ($entries as $entry) {
            if (!in_array($entry->NodeID, $nodes) && $entry->NodeID > 0) {
                $nodes[] = $entry->NodeID;
            }
        }
        return $this->json($nodes);
    }

    /**
     * Gets a list of the GatheringPoints that we have position for, per map
     *
     * @Route("/mappy/nodes", name="mappy_data_nodes")
     */
    public function getNodes()
    {
        $entries = $this->mappy->getFullData();
        $nodes = [];
        foreach ($entries as $entry) {
            if (!isset($nodes[$entry->getMapID()])) {
                $nodes[$entry->getMapID()] = [];
            }
            if (!in_array($entry->NodeID, $nodes[$entry->getMapID()]) && $entry->NodeID > 0) {
                $nodes[$entry->getMapID()][] = $entry->NodeID;
            }
        }
        return $this->json($nodes);
    }

    /**
     * Removes a mappy entry per id
     *
     * @Route("/mappy/entry/{id}", name="mappy_entry_delete", methods={"DELETE", "OPTIONS"})
     */
    public function deleteEntry(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->empty();
        }
        $user = $this->users->getUserByApiKey($request->get(ApiRequest::KEY_FIELD));
        $user->mustBeAdmin();
        $entityId = $request->get('id');
        $this->mappy->deleteEntry($entityId);
        return $this->json([
            'deleted' => $entityId
        ]);
    }

    /**
     * Removes everything for a given map
     *
     * @Route("/mappy/map/{id}", name="mappy_map_delete", methods={"DELETE", "OPTIONS"})
     */
    public function deleteMap(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->empty();
        }
        $user = $this->users->getUserByApiKey($request->get(ApiRequest::KEY_FIELD));
        $user->mustBeAdmin();
        $mapId = $request->get('id');
        $deleted = $this->mappy->deleteMap($mapId);
        return $this->json([
            'deleted' => $deleted
        ]);
    }

    /**
     * Gets all mappy data as a giant json array
     *
     * @Route("/mappy/json", name="mappy_data_full_json")
     */
    public function getFullData()
    {
        $entries = $this->mappy->getFullData();
        return $this->json($entries);
    }

    /**
     * @Route("/mappy/submit", name="mappy_submit")
     */
    public function submit(Request $request)
    {
        $response = ['ok' => true];
        $json     = json_decode($request->getContent());

        if (empty($json)) {
            return $this->json($response);
        }

        $response['output'] = $this->mappy->save($json);

        return $this->json($response);
    }

    /**
     * @Route("/download", name="mappy_download")
     */
    public function download(Request $request)
    {
        $folder = __DIR__ . '/downloads';

        if (!is_dir($folder)) {
            mkdir($folder);
        }

        $date       = date('Y-m-d-h-i-s');
        $filename   = "{$folder}/xivapi_mappy_{$date}.csv";
        $repository = null;

        switch ($request->get('data')) {
            default:
                throw new NotFoundHttpException();

            case 'map_data':
                $repository = $this->mappy->getMapPositionRepository();
                break;
        }

        Arrays::repositoryToCsv($repository, $filename);
        return $this->file(new File($filename));
    }
}
