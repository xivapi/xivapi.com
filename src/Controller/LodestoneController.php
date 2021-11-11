<?php

namespace App\Controller;

use App\Common\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lodestone\Api;

class LodestoneController extends Controller
{

    /**
     * @Route("/lodestone")
     * @Route("/Lodestone")
     */
    public function lodestone()
    {
        return $this->json(
            Redis::Cache()->get('lodestone')
        );
    }

    /**
     * @Route("/lodestone/banners")
     * @Route("/Lodestone/Banners")
     */
    public function lodestoneBanners()
    {
        return $this->json(
            (new Api())->lodestone()->banners()
        );
    }

    /**
     * @Route("/lodestone/news")
     * @Route("/Lodestone/News")
     */
    public function lodestoneNews()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/topics")
     * @Route("/Lodestone/Topics")
     */
    public function lodestoneTopics()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/notices")
     * @Route("/Lodestone/Notices")
     */
    public function lodestoneNotices()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/maintenance")
     * @Route("/Lodestone/Maintenance")
     */
    public function lodestoneMaintenance()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/updates")
     * @Route("/Lodestone/Updates")
     */
    public function lodestoneUpdates()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/status")
     * @Route("/Lodestone/Status")
     */
    public function lodestoneStatus()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/worldstatus")
     * @Route("/Lodestone/WorldStatus")
     */
    public function lodestoneWorldStatus()
    {
        return $this->json(
            (new Api())->lodestone()->worldstatus()
        );
    }

    /**
     * @Route("/lodestone/devblog")
     * @Route("/Lodestone/DevBlog")
     */
    public function lodestoneDevBlog()
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/devposts")
     * @Route("/Lodestone/DevPosts")
     */
    public function lodestoneDevPosts(Request $request)
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/feasts")
     * @Route("/Lodestone/Feasts")
     */
    public function lodestoneFeats(Request $request)
    {
        throw new \Exception("Not implemented (yet)");
    }

    /**
     * @Route("/lodestone/deepdungeon")
     * @Route("/Lodestone/DeepDungeon")
     */
    public function lodestoneDeepDungeon(Request $request)
    {
        throw new \Exception("Not implemented (yet)");
    }
    
    /**
     * @Route("/lodestone/heavenonhigh")
     * @Route("/Lodestone/HeavenOnHigh")
     */
    public function lodestoneHeavenOnHigh(Request $request)
    {
        throw new \Exception("Not implemented (yet)");
    }
}
