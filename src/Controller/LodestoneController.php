<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Redis\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lodestone\Api;

class LodestoneController extends Controller
{
    const CACHE_DURATION = (60 * 60);

    /** @var AppManager */
    private $apps;
    /** @var Cache */
    private $cache;

    public function __construct(AppManager $apps, Cache $cache)
    {
        $this->apps = $apps;
        $this->cache = $cache;
    }

    /**
     * @Route("/lodestone")
     * @Route("/Lodestone")
     */
    public function lodestone()
    {
        return $this->json(
            $this->cache->get('lodestone')
        );
    }

    /**
     * @Route("/lodestone/banners")
     * @Route("/Lodestone/Banners")
     */
    public function lodestoneBanners()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneBanners();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/news")
     * @Route("/Lodestone/News")
     */
    public function lodestoneNews()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNews();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/topics")
     * @Route("/Lodestone/Topics")
     */
    public function lodestoneTopics()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneTopics();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/notices")
     * @Route("/Lodestone/Notices")
     */
    public function lodestoneNotices()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNotices();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/maintenance")
     * @Route("/Lodestone/Maintenance")
     */
    public function lodestoneMaintenance()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneMaintenance();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/updates")
     * @Route("/Lodestone/Updates")
     */
    public function lodestoneUpdates()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneUpdates();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/status")
     * @Route("/Lodestone/Status")
     */
    public function lodestoneStatus()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneStatus();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/worldstatus")
     * @Route("/Lodestone/WorldStatus")
     */
    public function lodestoneWorldStatus()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getWorldStatus();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/devblog")
     * @Route("/Lodestone/DevBlog")
     */
    public function lodestoneDevBlog()
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getDevBlog();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/devposts")
     * @Route("/Lodestone/DevPosts")
     */
    public function lodestoneDevPosts(Request $request)
    {
        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getDevPosts();
            $this->cache->set(__METHOD__, $data, self::CACHE_DURATION);
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
}
