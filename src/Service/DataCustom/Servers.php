<?php

namespace App\Service\DataCustom;

use App\Common\Game\GameServers;
use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Servers extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('World');
    
        foreach ($ids as $id) {
            $key = "xiv_World_{$id}";
            $world = Redis::Cache()->get($key);
    
            $world->InGame = in_array($world->Name_en, GameServers::LIST);
        
            // save
            Redis::Cache()->set($key, $world, self::REDIS_DURATION);
        }
    }
}
