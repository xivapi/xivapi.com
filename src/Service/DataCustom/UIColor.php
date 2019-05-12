<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class UIColor extends ManualHelper
{
    const PRIORITY = 800;
    
    public function handle()
    {
        return;
        foreach (Redis::Cache()->get("ids_UIColor") as $id) {
            $color = Redis::Cache()->get("xiv_UIColor_{$id}");
            
            if (!$color) {
                continue;
            }

            // todo - this needs remapping on the ex.json and re-importing

            $color->ColorAHexAlpha = str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT);
            $color->ColorBHexAlpha = str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT);
            $color->ColorAHex      = substr(str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT), 0, 6);
            $color->ColorBHex      = substr(str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT), 0, 6);

            Redis::Cache()->set("xiv_UIColor_{$id}", $color, self::REDIS_DURATION);
        }
    }
}
