<?php

namespace App\Controller;

use App\Common\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lodestone\Api;

class LodestoneController extends Controller
{
    const CACHE_DURATION = (60 * 60);

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
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->lodestone()->banners();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
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
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->lodestone()->worldstatus();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
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
