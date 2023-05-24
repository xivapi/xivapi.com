<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class ItemFood extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('ItemFood');
    
        foreach ($ids as $id) {
            $key = "xiv_ItemFood_{$id}";
            $itemFood = Redis::Cache(true)->get($key);

            // todo
        
            // save
            Redis::Cache(true)->set($key, $itemFood, self::REDIS_DURATION);
        }
    }
}
