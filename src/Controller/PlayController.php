<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class PlayController extends AbstractController
{
    /**
     * @Route("/play/search", name="search_play")
     */
    public function search()
    {
        return $this->render('search/play.html.twig');
    }
}
