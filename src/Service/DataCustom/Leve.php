<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Leve extends ManualHelper
{
    // run after "CraftLeve"
    const PRIORITY = 125;

    public function handle()
    {
        foreach (Redis::Cache()->get("ids_Leve") as $id) {
            $key  = "xiv_Leve_{$id}";
            $leve = Redis::Cache()->get($key);
            // ---------------------------------------------------

            // defaults
            $leve->CraftLeve        = null;
            $leve->CompanyLeve      = null;
            $leve->GatheringLeve    = null;
            $leve->BattleLeve       = null;

            // CraftLeve = 917500 > 930000
            if ($leve->DataIdTargetID >= 917500 && $leve->DataIdTargetID <= 930000) {
                $leve->CraftLeve = Redis::Cache()->get("xiv_CraftLeve_{$leve->DataIdTargetID}");
            }

            // CompanyLeve = 196600 > 196700
            if ($leve->DataIdTargetID >= 196600 && $leve->DataIdTargetID <= 199000) {
                $leve->CompanyLeve = Redis::Cache()->get("xiv_CompanyLeve_{$leve->DataIdTargetID}");
            }

            // GatheringLeve = 131070 > 131300
            if ($leve->DataIdTargetID >= 131070 && $leve->DataIdTargetID <= 135000) {
                $leve->GatheringLeve = Redis::Cache()->get("xiv_GatheringLeve_{$leve->DataIdTargetID}");
            }

            // BattleLeve = 65530 > 65800
            if ($leve->DataIdTargetID >= 65530 && $leve->DataIdTargetID <= 70000) {
                $leve->BattleLeve = Redis::Cache()->get("xiv_BattleLeve_{$leve->DataIdTargetID}");
            }

            unset($leve->DataId);
            $leve->{$leve->DataIdTarget . "TargetID"} = $leve->DataIdTargetID;
            unset($leve->DataIdTargetID);

            // ---------------------------------------------------
            Redis::Cache()->set($key, $leve, self::REDIS_DURATION);
        }
    }
}
