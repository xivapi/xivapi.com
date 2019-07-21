<?php

namespace App\Service\DataCustom;

use App\Service\Data\CsvReader;
use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;
use stdClass;

class Item extends ManualHelper
{
    const PRIORITY = 20;

    public function handle()
    {
        $ids       = $this->getContentIds('Item');
        $recipeIds = $this->getContentIds('Recipe');
        $recipes   = array();

        foreach ($recipeIds as $recipeId) {
            $recipes[] = Redis::Cache()->get("xiv_Recipe_{$recipeId}");
        }

        foreach ($ids as $id) {
            $key  = "xiv_Item_{$id}";
            $item = Redis::Cache()->get($key);

            // this is prep for something else
            $item->Materia = $item->Materia ?? null;

            // do stuff
            $this->processStats($item);
            $this->itemLinkItemUiCategoryToItemKind($item);
            $this->linkRecipes($item, $recipes);
            Redis::Cache()->set($key, $item, self::REDIS_DURATION);
        }
    }

    /**
     * This adds the search category to items that don't have it
     */
    private function itemLinkItemUiCategoryToItemKind($item)
    {
        $itemUiCategory_TO_itemKind = [
            // arms
            1 => [
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
                84, 87, 88, 89, 96, 97, 98,
            ],
            // tools
            2 => [
                12, 13, 14, 15, 16, 17, 18, 19, 20,
                21, 22, 23, 24, 25, 26, 27, 28, 29,
                30, 31, 32, 33, 99
            ],
            // armor
            3 => [
                11, 34, 35, 36, 37, 38, 39
            ],
            // accessories
            4 => [
                40, 41, 42, 43
            ],
            // medicines and meals
            5 => [
                44, 45, 46, 47,
            ],
            // Materials
            6 => [
                48, 49, 50, 51, 52, 53, 54, 55, 56
            ],
        ];

        $itemKindId = false;

        // find the slot id
        foreach ($itemUiCategory_TO_itemKind as $kindId => $uiCategoryList) {
            if (isset($item->ItemUICategory->ID) && in_array($item->ItemUICategory->ID, $uiCategoryList)) {
                $itemKindId = $kindId;
                break;
            }
        }

        // 7 = other
        $itemKindId     = $itemKindId ?: 7;
        $itemKindCsv    = CsvReader::Get(__DIR__ . '/Csv/ItemKind.csv');
        $itemKindCsv    = $itemKindCsv[$itemKindId];
        $item->ItemKind = $itemKindCsv;
    }

    private function linkRecipes($item, $recipes)
    {
        foreach ($recipes as $recipe) {
            if ($recipe->ItemResult->ID == $item->ID) {
                $item->Recipes   = $item->Recipes ?? array();
                $item->Recipes[] = $recipe;
            }
        }
    }

    private function processStats($item)
    {
        foreach ($item as $key => $baseParam) {
            if (isset($baseParam) && preg_match('/^BaseParam(\d+)$/', $key, $matches, PREG_OFFSET_CAPTURE)) {
                $valuePropName  = 'BaseParamValue' . $matches[1][0];
                $statName       = str_replace(' ', '', $baseParam->Name_en);
                $item->Stats    = $item->Stats ?? new stdClass;
                $statsEntry     = new stdClass;
                $statsEntry->ID = $baseParam->ID;
                $statsEntry->NQ = $item->$valuePropName;
                if ($item->CanBeHq == 1) {
                    $hqStatValue = $item->$valuePropName;
                    foreach ($item as $specialKey => $baseParamSpecial) {
                        if (preg_match('/^BaseParamSpecial(\d+)$/', $specialKey, $specialMatches, PREG_OFFSET_CAPTURE)) {
                            $hqStatBonusPropName = 'BaseParamValueSpecial' . $matches[1][0];
                            $hqStatValue         += $item->$hqStatBonusPropName;
                            break;
                        }
                    }
                    $statsEntry->HQ = $hqStatValue;
                }
                $item->Stats->$statName = $statsEntry;
            }
        }
        $bonusActions = array(844, 845, 846);
        if (isset($item->ItemAction) && in_array($item->ItemAction->Type, $bonusActions)) {
            $food          = Redis::cache()->get("xiv_ItemFood_{$item->ItemAction->Data1}");
            $item->Bonuses = new stdClass;
            for ($i = 0; $i < 2; $i++) {
                $bonusEntry    = new stdClass;
                $baseParamKey  = "BaseParam{$i}";
                $valueKey      = "Value{$i}";
                $valueHQKey    = "ValueHQ{$i}";
                $isRelativeKey = "IsRelative${i}";
                $maxKey        = "Max${i}";
                $maxHQKey      = "MaxHQ${i}";
                $statName      = str_replace(' ', '', $food->$baseParamKey->Name_en);

                $bonusEntry->ID       = $food->$baseParamKey->ID;
                $bonusEntry->Relative = $food->$isRelativeKey == 1;

                if ($food->$valueKey > 0) {
                    $bonusEntry->Value = $food->$valueKey;
                    if ($bonusEntry->Relative) {
                        $bonusEntry->Max = $food->$maxKey;
                    }
                    $bonusEntry->ValueHQ = $food->$valueHQKey;
                    if ($bonusEntry->Relative) {
                        $bonusEntry->MaxHQ = $food->$maxHQKey;
                    }
                }
                $item->Bonuses->$statName = $bonusEntry;
            }
        }
    }
}
