<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class LodestoneStatisticsController extends Controller
{
    /**
     * @Route("/queue/statistics")
     */
    public function statistics()
    {
        $json = json_decode(
            file_get_contents(__DIR__.'/../Service/LodestoneQueue/stats.json'),
            true
        );

        return $this->render('statistics/index.html.twig', [
            'stats' => $json
        ]);
    }
}
