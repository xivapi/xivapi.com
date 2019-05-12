<?php

namespace App\Controller;

use App\Common\Game\GameServers;
use App\Service\Content\GameData;
use App\Service\GamePatch\Patch;
use App\Common\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class XivGameContentController extends AbstractController
{
    /** @var GameData */
    private $game;

    public function __construct(GameData $game)
    {
        $this->game = $game;
    }

    /**
     * @Route("/PatchList")
     * @Route("/patchlist")
     */
    public function patches()
    {
        return $this->json((new Patch())->get());
    }
    
    /**
     * @Route("/Servers")
     * @Route("/servers")
     */
    public function servers()
    {
        return $this->json(GameServers::LIST);
    }
    
    /**
     * @Route("/Servers/DC")
     * @Route("/servers/dc")
     */
    public function serversByDataCenter()
    {
        return $this->json(GameServers::LIST_DC);
    }

    /**
     * @Route("/Content")
     * @Route("/content")
     */
    public function content()
    {
        return $this->json($this->game->content());
    }
    
    /**
     * @Route("/Item/Lodestone")
     * @Route("/item/lodestone")
     */
    public function itemLodestone()
    {
        $key = 'xiv_item_lodestone_ids';
        
        if ($data = Redis::Cache()->get($key)) {
            return $this->json($data);
        }
        
        $data = $this->game->getLodestoneIds();
        Redis::Cache()->set($key, $data);
        
        return $this->json($data);
    }
    
    /**
     * @Route("/{contentName}")
     */
    public function contentList(Request $request, $contentName)
    {
        return $this->json(
            $this->game->list($request, $contentName)
        );
    }

    /**
     * @Route("/{contentName}/schema")
     */
    public function schema($contentName)
    {
        return $this->json(
            $this->game->schema($contentName)
        );
    }

    /**
     * @Route("/{contentName}/ids")
     */
    public function ids($contentName)
    {
        $contentName = $this->game->validate($contentName);

        return $this->json(
            Redis::Cache()->get("ids_{$contentName}")
        );
    }

    /**
     * @Route("/{contentName}/{contentId}")
     */
    public function contentData($contentName, $contentId)
    {
        return $this->json(
            $this->game->one($contentName, $contentId)
        );
    }
}
