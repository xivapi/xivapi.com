<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class Stain extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Stain');
        
        foreach ($ids as $id) {
            $key1 = "xiv_Stain_{$id}";
            $content1 = $this->redis->get($key1);
            $content1->Hex = str_pad(dechex($content1->Color), 6, '0', STR_PAD_LEFT);
            $this->redis->set($key1, $content1, self::REDIS_DURATION);
        }
    }
}
