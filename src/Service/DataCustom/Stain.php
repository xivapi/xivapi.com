<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Stain extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Stain');
        
        foreach ($ids as $id) {
            $key1 = "xiv_Stain_{$id}";
            $content1 = Redis::Cache(true)->get($key1);
            $content1->Hex = str_pad(dechex($content1->Color), 6, '0', STR_PAD_LEFT);
            Redis::Cache(true)->set($key1, $content1, self::REDIS_DURATION);
        }
    }
}
