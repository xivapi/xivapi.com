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
     * @Route("/mappy/map/{mapId}", name="mappy_data_map")
     */   
    public function getMap(int $mapId) 
    {
        $entries = $this->repository->findBy(['MapID' => $mapId]);
        return $this->json($entries);
    }

    /**
     * @Route("/mappy/submit", name="mappy_submit")
     */
    public function submit(Request $request)
    {
        $response = [ 'ok' => true ];
        $json     = json_decode($request->getContent());
        
        if (empty($json)) {
            return $this->json($response);
        }
        
        $this->mappy->save($json->data);
        
        return $this->json($response);
    }
    
    /**
     * @Route("/download", name="mappy_download")
     */
    public function download(Request $request)
    {
        $folder = __DIR__.'/downloads';
        
        if (!is_dir($folder)) {
            mkdir($folder);
        }
        
        $date       = date('Y-m-d-h-i-s');
        $filename   = "{$folder}/xivapi_mappy_{$date}.csv";
        $repository = null;
        
        switch($request->get('data')) {
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
