<?php

namespace App\Service\DataCustom;

use App\Common\Service\Redis\Redis;
use App\Service\Content\ManualHelper;
use App\Service\GamePatch\Patch;

class PatchHelper extends ManualHelper
{
    const PRIORITY = 20;

    public function handle()
    {
        $contentNames = Redis::Cache()->get('content');
        $patchDb      = new Patch();
        foreach ($contentNames as $contentName) {
            $patchDataFile = file_get_contents("./data/ffxiv-datamining-patches/patchdata/" . $contentName . ".json");
            $patchData     = json_decode($patchDataFile);
            foreach (Redis::Cache()->get("ids_{$contentName}") as $id) {
                $doc                = "xiv_{$contentName}_{$id}";
                $content            = Redis::Cache()->get("xiv_{$contentName}_{$id}");
                $content->Patch     = $patchData->{$id};
                $content->GamePatch = $patchDb->getPatchAtID($id);
                Redis::Cache()->set($doc, $content, self::REDIS_DURATION);
            }
        }
    }
}
