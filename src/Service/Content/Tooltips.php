<?php

namespace App\Service\Content;

class Tooltips
{
    public static function get($category, $content)
    {
        switch ($category) {
            case 'Achievement':     return self::Achievement($content);
            case 'Item':            return self::Item($content);
        }
    }

    public static function Achievement($content)
    {
        return [
            $content->ID,
            $content->Name_en,
            $content->Icon,
            1, // rarity

            $content->AchievementCategory->Name_en ?? null,
            $content->AchievementCategory->AchievementKind->Name_en ?? null,
            $content->Description_en,
            $content->Points,
        ];
    }

    public static function Item($content)
    {
        return [
            $content->ID,
            $content->Name_en,
            $content->Icon,
            $content->Rarity,

            $content->Description_en,
            $content->LevelEquip,
            $content->LevelItem,
            $content->ClassJobCategory->Name_en ?? null,
            $content->ClassJobUse->NameEnglish_en ?? null,
            $content->ItemUICategory->Name_en ?? null,

            // is
            $content->IsAdvancedMeldingPermitted,
            $content->IsCollectable,
            $content->IsCrestWorthy,
            $content->IsDyeable,
            $content->IsGlamourous,
            $content->IsPvP,
            $content->IsUnique,
            $content->IsUntradable,

            // stats
            $content->Block,
            $content->BlockRate,
            $content->CooldownS,
            $content->DamageMag,
            $content->DamagePhys,
            $content->DefenseMag,
            $content->DefensePhys,
            $content->DelayMs,
            $content->BaseParam0->Name_en ?? null,
            $content->BaseParam1->Name_en ?? null,
            $content->BaseParam2->Name_en ?? null,
            $content->BaseParam3->Name_en ?? null,
            $content->BaseParam4->Name_en ?? null,
            $content->BaseParam5->Name_en ?? null,
            $content->BaseParamValue0 ?? null,
            $content->BaseParamValue1 ?? null,
            $content->BaseParamValue2 ?? null,
            $content->BaseParamValue3 ?? null,
            $content->BaseParamValue4 ?? null,
            $content->BaseParamValue5 ?? null,
        ];
    }
}
