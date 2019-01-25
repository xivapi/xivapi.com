<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

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
            $itemAction = $this->redis->get($key);
            
            // todo ---
            
            // save
            $this->redis->set($key, $itemAction, self::REDIS_DURATION);
        }
    }
}
