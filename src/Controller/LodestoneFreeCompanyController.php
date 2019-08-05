<?php

namespace App\Controller;

use Intervention\Image\ImageManager;
use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class LodestoneFreeCompanyController extends AbstractController
{
    /**
     * @Route("/FreeCompany/Search")
     * @Route("/freecompany/search")
     */
    public function search(Request $request)
    {
        if (empty(trim($request->get('name')))) {
            throw new NotAcceptableHttpException('You must provide a name to search.');
        }
        
        return $this->json(
            (new Api())->freecompany()->search(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page') ?: 1
            )
        );
    }
    
    /**
     * @Route("/FreeCompany/{lodestoneId}")
     * @Route("/freecompany/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
    
        // initialise api
        $api = new Api();
    
        $response = (Object)[
            'FreeCompany' => $api->freecompany()->get($lodestoneId),
            'FreeCompanyMembers' => null,
        ];
    
        // choose which content you want
        $data = $request->get('data') ? explode(',', strtoupper($request->get('data'))) : [];
        $content = (object)[
            'FCM' => in_array('FCM', $data),
        ];
    
        // Free Company Members
        if ($content->FCM) {
            $members = [];
        
            // grab 1st page, so we know if there is more than 1 page
            $first = $api->freecompany()->members($lodestoneId, 1);
            $members = $first ? array_merge($members, $first->Results) : $members;
        
            if ($first && $first->Pagination->PageTotal > 1) {
                // parse the rest of pages
                $api->config()->useAsync();
                foreach (range(2, $first->Pagination->PageTotal) as $page) {
                    $api->freecompany()->members($lodestoneId, $page);
                }
            
                foreach ($api->http()->settle() as $res) {
                    $members = array_merge($members, $res->Results);
                }
                $api->config()->useSync();
            }
        
            $response->FreeCompanyMembers = $members;
        }
    
        $response->FreeCompany->ID = $lodestoneId;
    
        return $this->json($response);
    }

    /**
     * @Route("/FreeCompany/{lodestoneId}/Icon")
     * @Route("/freecompany/{lodestoneId}/icon")
     */
    public function icon($lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
    
        // initialise api
        $api = new Api();
        
        // grab FC
        $freecompany = $api->freecompany()->get($lodestoneId);
        
        /**
         * Filename for FC icon
         */
        $filename = __DIR__.'/../../public/fc/'. $lodestoneId .'.png';

        /**
         * Check if it exists, if so, spit it out
         */
        if (file_exists($filename)) {
            return new BinaryFileResponse($filename, 200);
        }

        /** @var ImageManager $manager */
        $manager = new ImageManager(['driver' => 'gd']);
        $img = $manager->make(file_get_contents($freecompany->Crest[0]));

        /**
         * Insert the other 2 layers
         */
        if (isset($freecompany->Crest[1])) {
            $img->insert(
                $manager->make($freecompany->Crest[1])
            );
        }

        if (isset($freecompany->Crest[2])) {
            $img->insert(
                $manager->make($freecompany->Crest[2])
            );
        }

        /**
         * Save and compress
         */
        $img->save($filename);

        usleep(500 * 1000);
        $img = imagecreatefrompng($filename);
        imagejpeg($img, $filename, 95);
        usleep(500 * 1000);

        return new BinaryFileResponse($filename, 200);
    }
}
