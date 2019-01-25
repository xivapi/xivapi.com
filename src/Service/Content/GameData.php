<?php

namespace App\Service\Content;

use App\Service\Redis\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameData
{
    /** @var Cache */
    private $cache = null;
    /** @var ContentList */
    private $contentList;

    public function __construct(Cache $cache, ContentList $contentList)
    {
        $this->cache = $cache;
        $this->contentList = $contentList;
    }

    /**
     * get a single piece of content from the cache
     */
    public function one(string $contentName, int $contentId)
    {
        $contentName = $this->validate($contentName);
        $content = $this->cache->get("xiv_{$contentName}_{$contentId}");
    
        if (!$content) {
            throw new \Exception("Game Data does not exist: {$contentName} {$contentId}");
        }

        // add additional data
        $additional = [
            'xiv2',
            'xiv_korean',
            'xiv_chinese'
        ];

        foreach($additional as $add) {
            $data = $this->cache->get("{$add}_{$contentName}_{$contentId}");

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
        return $this->cache->get("schema_{$contentName}");
    }

    /**
     * Get the game content list
     */
    public function content()
    {
        return $this->cache->get('content');
    }

    /**
     * Validate the passed content name, this will
     */
    private function validate(string $contentName): string
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
