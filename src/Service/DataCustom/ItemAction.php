<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Service\Redis\Redis;

class ItemAction extends ManualHelper
{
    const PRIORITY = 20;
    
    /**
     * todo - Process using: https://github.com/viion/ffxiv-datamining/blob/master/research/item_actions.md
     */
    public function handle()
    {
        $ids = $this->getContentIds('ItemAction');
        
        foreach ($ids as $id) {
            $key = "xiv_ItemAction_{$id}";
            $itemAction = Redis::Cache()->get($key);
            
            // todo ---
            
            // save
            Redis::Cache()->set($key, $itemAction, self::REDIS_DURATION);
        }
    }
}
