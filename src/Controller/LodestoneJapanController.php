<?php

namespace App\Controller;

use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This is intended to be used only on lodestone.xivapi.com
 */
class LodestoneJapanController extends Controller
{
    /**
     * @Route("/japan/search/character")
     */
    public function searchCharacter(Request $request)
    {
        return $this->json(
            (new Api())->searchCharacter(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page')
            )
        );
    }

    /**
     * @Route("/japan/search/freecompany")
     */
    public function searchFreeCompany(Request $request)
    {
        return $this->json(
            (new Api())->searchFreeCompany(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page')
            )
        );
    }

    /**
     * @Route("/japan/search/linkshell")
     */
    public function searchLinkshell(Request $request)
    {
        return $this->json(
            (new Api())->searchLinkshell(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page')
            )
        );
    }

    /**
     * @Route("/japan/search/pvpteam")
     */
    public function searchPvPTeam(Request $request)
    {
        return $this->json(
            (new Api())->searchPvPTeam(
                $request->get('name'),
                ucwords($request->get('server')),
                $request->get('page')
            )
        );
    }
}
