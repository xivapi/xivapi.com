<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class ItemFood extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('ItemFood');
    
        foreach ($ids as $id) {
            $key = "xiv_ItemFood_{$id}";
            $itemFood = $this->redis->get($key);

            // todo
        
            // save
            $this->redis->set($key, $itemFood, self::REDIS_DURATION);
        }
    }
}
