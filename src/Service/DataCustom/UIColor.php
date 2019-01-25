<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class UIColor extends ManualHelper
{
    const PRIORITY = 800;
    
    public function handle()
    {
        return;
        
        foreach ($this->redis->get("ids_UIColor") as $id) {
            $color = $this->redis->get("xiv_UIColor{$id}");

            // todo - this needs remapping on the ex.json and re-importing

            $color->ColorAHexAlpha = str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT);
            $color->ColorBHexAlpha = str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT);
            $color->ColorAHex      = substr(str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT), 0, 6);
            $color->ColorBHex      = substr(str_pad(dechex($color->ColorA), 8, '0', STR_PAD_LEFT), 0, 6);

            $this->redis->set("xiv_UIColor{$id}", $color, self::REDIS_DURATION);
        }
    }
}
