<?php

namespace App\Service\Content;

use App\Entity\ItemIcon;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameData
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ContentList */
    private $contentList;

    public function __construct(EntityManagerInterface $em, ContentList $contentList)
    {
        $this->em = $em;
        $this->contentList = $contentList;
    }
    
    /**
     * Returns all item ids to lodestone ids
     */
    public function getLodestoneIds()
    {
        $list = [];
        
        /** @var ItemIcon $item */
        foreach ($this->em->getRepository(ItemIcon::class)->findAll() as $item) {
            $list[] = [
                'ID'            => $item->getItem(),
                'LodestoneID'   => $item->getLodestoneId(),
                'LodestoneIcon' => $item->getLodestoneIcon(),
                'Status'        => $item->getStatus(),
            ];
        }
        
        return $list;
    }
    
    /**
     * get a single piece of content from the cache
     */
    public function one(string $contentName, string $contentId)
    {
        $contentName = $this->validate($contentName);
        $content = Redis::Cache()->get("xiv_{$contentName}_{$contentId}");
        
        if (!$content) {
            throw new NotFoundHttpException("Game Data does not exist: {$contentName} {$contentId}");
        }

        // add additional data
        $additional = [
            'xiv2',
            'xiv_korean',
            'xiv_chinese',
        ];

        foreach($additional as $add) {
            $data = Redis::Cache()->get("{$add}_{$contentName}_{$contentId}");

            if (empty($data)) {
                continue;
            }

            foreach ($data as $field => $value) {
                $content->{$field} = $value;
            }
        }

        return $content;
    }

    public function list(Request $request, string $contentName)
    {
        $contentName = $this->validate($contentName);
        return $this->contentList->get($request, $contentName);
    }

    /**
     * Get the schema for a piece of content
     */
    public function schema(string $contentName)
    {
        $contentName = $this->validate($contentName);
        return Redis::Cache()->get("schema_{$contentName}");
    }

    /**
     * Get the game content list
     */
    public function content()
    {
        return Redis::Cache()->get('content');
    }

    /**
     * Validate the passed content name, this will
     */
    public function validate(string $contentName): string
    {
        $contentName = $this->getContentName($contentName);

        if (!$contentName) {
            throw new NotFoundHttpException("No content data found for: {$contentName}");
        }

        return $contentName;
    }

    /**
     * Get the real content name
     */
    private function getContentName($string): ?string
    {
        foreach ($this->content() as $name) {
            if (strtolower($string) === strtolower($name)) {
                return $name;
            }
        }

        return false;
    }
}
