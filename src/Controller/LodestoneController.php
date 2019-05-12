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
            $data = (new Api())->getLodestoneBanners();
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
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNews();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/topics")
     * @Route("/Lodestone/Topics")
     */
    public function lodestoneTopics()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneTopics();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/notices")
     * @Route("/Lodestone/Notices")
     */
    public function lodestoneNotices()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNotices();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/maintenance")
     * @Route("/Lodestone/Maintenance")
     */
    public function lodestoneMaintenance()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneMaintenance();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/updates")
     * @Route("/Lodestone/Updates")
     */
    public function lodestoneUpdates()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneUpdates();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/status")
     * @Route("/Lodestone/Status")
     */
    public function lodestoneStatus()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getLodestoneStatus();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/worldstatus")
     * @Route("/Lodestone/WorldStatus")
     */
    public function lodestoneWorldStatus()
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getWorldStatus();
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
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getDevBlog();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/devposts")
     * @Route("/Lodestone/DevPosts")
     */
    public function lodestoneDevPosts(Request $request)
    {
        if (!$data = Redis::Cache()->get(__METHOD__)) {
            $data = (new Api())->getDevPosts();
            Redis::Cache()->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/feasts")
     * @Route("/Lodestone/Feasts")
     */
    public function lodestoneFeats(Request $request)
    {
        return $this->json((new Api())->getFeast(
            $request->get('season'),
            $request->request->all()
        ));
    }

    /**
     * @Route("/lodestone/deepdungeon")
     * @Route("/Lodestone/DeepDungeon")
     */
    public function lodestoneDeepDungeon(Request $request)
    {
        return $this->json((new Api())->getDeepDungeon(
            $request->request->all()
        ));
    }
    
    /**
     * @Route("/lodestone/heavenonhigh")
     * @Route("/Lodestone/HeavenOnHigh")
     */
    public function lodestoneHeavenOnHigh(Request $request)
    {
        return $this->json((new Api())->getHeavenOnHigh(
            $request->request->all()
        ));
    }
}
