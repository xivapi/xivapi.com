<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

/**
 * Build connections between game data
 */
class Links extends ManualHelper
{
    const PRIORITY = 9998;
    
    public function handle()
    {
        // grab content
        $content = Redis::Cache()->get('content');
        $this->io->text(sprintf('Processing %s pieces of content', count($content)));
        
        $this->io->progressStart(count($content));
        foreach($content as $contentName) {
            $this->io->progressAdvance();
            
            // grab ids
            $ids = Redis::Cache()->get("ids_{$contentName}");
            if (!$ids) {
                continue;
            }

            foreach ($ids as $contentId) {
                $key1 = "xiv_{$contentName}_{$contentId}";
                $key2 = "connections_{$contentName}_{$contentId}";
            
                // grab data
                $content     = Redis::Cache()->get($key1);
                $connections = Redis::Cache()->get($key2);
            
                // rebuild
                $gameContentLinks = [];
                if ($connections) {
                    foreach (array_keys((array)$connections) as $connection) {
                        [$linkName, $linkId, $linkColumn] = explode('_', $connection);
                    
                        if (!isset($gameContentLinks[$linkName][$linkColumn])) {
                            $gameContentLinks[$linkName][$linkColumn] = [];
                        }
                    
                        $gameContentLinks[$linkName][$linkColumn][] = $linkId;
                    }
                }
            
                // set game content links
                $content->GameContentLinks = $gameContentLinks;
                Redis::Cache()->set($key1, $content, self::REDIS_DURATION);
            }
        }
        
        $this->io->progressFinish();
        $this->io->text('Content links built');
    }
}
