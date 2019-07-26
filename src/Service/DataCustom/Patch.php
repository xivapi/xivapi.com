<?php

namespace App\Service\DataCustom;

use App\Common\Service\Redis\Redis;
use App\Service\Content\ManualHelper;

class Patch extends ManualHelper
{
    const PRIORITY = 20;

    public function handle()
    {
        foreach (Redis::Cache()->get('content') as $contentName) {
            foreach (Redis::Cache()->get("ids_{$contentName}") as $id) {
                $doc            = "xiv_{$contentName}_{$id}";
                $content        = Redis::Cache()->get("xiv_{$contentName}_{$id}");
                $patchDataFile  = file_get_contents(__DIR__ . "../../../data/ffxiv-datamining-patches/patchdata/" . $contentName . ".json");
                $patchData      = json_decode($patchDataFile);
                $content->Patch = $patchData[$id];
                Redis::Cache()->set($doc, $content, self::REDIS_DURATION);
            }
        }
    }
}
