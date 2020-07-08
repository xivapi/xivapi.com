<?php

namespace App\Service\Content;

use App\Common\Utils\Arrays;
use App\Common\Service\Redis\Redis;
use App\Common\Utils\Language;

class LodestoneCharacter
{
    public static function extendCharacterDataHandler($name, $data, $fields)
    {
        // grab content and ensure it's an array
        $content = Redis::Cache()->get("xiv_{$name}_". $data->{$name});
        
        if (!$content) {
            return;
        }
        
        $data->{$name} = self::extendCharacterDataHandlerSimple($content, $fields);
    }
    
    public static function extendCharacterDataHandlerSimple($content, $fields)
    {
        $content = json_decode(json_encode($content), true);
        
        if (!$content) {
            return null;
        }
        
        // build new array using fields
        $arr = [];
        foreach ($fields as $field) {
            // replace gender and language tags
            $field = str_replace('[LANG]', Language::current(), $field);
            
            // grab field
            $arr[$field] = Arrays::getArrayValueFromDotNotation($content, $field);
            
            // replace any _[lang] with non lang ones
            if (substr_count($field, '_') > 0) {
                $value = $arr[$field];
                unset($arr[$field]);
                
                $field = substr($field, 0, -3);
                $arr[$field] = $value;
            }
            
            if (substr_count($field, '.') > 0) {
                Arrays::handleDotNotationToArray($arr, $field, $arr[$field]);
                unset($arr[$field]);
            }
        }
        
        return json_decode(json_encode($arr));
    }
    
    public static function extendCharacterData($data)
    {
        if ($data == null) {
            return;
        }
        
        self::extendCharacterDataHandler('Title', $data, [
            "ID",
            "Icon",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);
        
        self::extendCharacterDataHandler('Race', $data, [
            "ID",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);
        
        self::extendCharacterDataHandler('Tribe', $data, [
            "ID",
            "Icon",
            "Url",
            "Name_[LANG]",
            "NameFemale_[LANG]"
        ]);
        
        self::extendCharacterDataHandler('Town', $data, [
            "ID",
            "Url",
            "Icon",
            "Name_[LANG]"
        ]);
        
        self::extendCharacterDataHandler('GuardianDeity', $data, [
            "ID",
            "Url",
            "Icon",
            "Name_[LANG]",
            "GuardianDeity_[LANG]"
        ]);
        
        //
        // Fix some female specifics
        //
        if ($data->Gender == 2) {
            // replace male with female value
            $data->Title->Name = $data->Title->NameFemale;
            $data->Race->Name  = $data->Race->NameFemale;
            $data->Tribe->Name = $data->Tribe->NameFemale;
        }
        
        // remove female values
        unset(
            $data->Title->NameFemale,
            $data->Race->NameFemale,
            $data->Tribe->NameFemale
        );
        
        //
        // Grand Company
        //
        $data->GenderID = $data->Gender;
        $gcGender = $data->Gender == 2 ? 'Female' : 'Male';
        
        $gcRankKeyArray = [
            null,
            "xiv_GCRankLimsa{$gcGender}Text_%s",
            "xiv_GCRankGridania{$gcGender}Text_%s",
            "xiv_GCRankUldah{$gcGender}Text_%s"
        ];
        
        $gcRankIconKeyArray = [
            null,
            "IconMaelstrom",
            "IconSerpents",
            "IconFlames"
        ];
        
        if (isset($data->GrandCompany->NameID) && !isset($data->GrandCompany->RankID)) {
            throw new \Exception('Fatal error: Grand Company Name ID found but Rank ID not found');
        }
        
        if (isset($data->GrandCompany->NameID) && isset($data->GrandCompany->RankID)) {
            $gcName = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_GrandCompany_{$data->GrandCompany->NameID}"),
                [
                    'ID',
                    'Url',
                    'Name_[LANG]',
                ]
            );
            
            $gcRankName = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get(sprintf($gcRankKeyArray[$data->GrandCompany->NameID], $data->GrandCompany->RankID)),
                [
                    'ID',
                    'Url',
                    'Name_[LANG]',
                ]
            );
            
            $gcRank = Redis::Cache()->get("xiv_GrandCompanyRank_{$data->GrandCompany->RankID}");
            $gcRankName->Icon = $gcRank->{$gcRankIconKeyArray[$data->GrandCompany->NameID]};
            unset($gcRank);
        }
        
        $data->GrandCompany = [
            'Company' => $gcName ?? null,
            'Rank'    => $gcRankName ?? null
        ];
        
        //
        // Class Jobs
        //
        foreach ($data->ClassJobs as $key => $classJob) {
            $classJob->Class = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_ClassJob_{$classJob->ClassID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );
            
            $classJob->Job = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_ClassJob_{$classJob->JobID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );
            
            unset($classJob->ClassID, $classJob->JobID);
        }
        
        //
        // Active class job
        //
        if ($data->ActiveClassJob) {
            $data->ActiveClassJob->Class = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_ClassJob_{$data->ActiveClassJob->ClassID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );
            $data->ActiveClassJob->Job = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_ClassJob_{$data->ActiveClassJob->JobID}"), [
                    'ID',
                    'Icon',
                    'Url',
                    'Name_[LANG]',
                    'Abbreviation_[LANG]',
                ]
            );
        }
        
        
        unset($data->ActiveClassJob->ClassID, $data->ActiveClassJob->JobID);
        
        //
        // Gear ClassJob
        //
        
        $data->GearSet->Class = self::extendCharacterDataHandlerSimple(
            Redis::Cache()->get("xiv_ClassJob_{$data->GearSet->ClassID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
            ]
        );
        $data->GearSet->Job = self::extendCharacterDataHandlerSimple(
            Redis::Cache()->get("xiv_ClassJob_{$data->GearSet->JobID}"), [
                'ID',
                'Icon',
                'Url',
                'Name_[LANG]',
                'Abbreviation_[LANG]',
            ]
        );
        unset(
            $data->GearSet->ClassID,
            $data->GearSet->JobID
        );
        
        //
        // Gear Attributes
        //
        foreach ($data->GearSet->Attributes as $id => $value) {
            $attr = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_BaseParam_{$id}"),
                [
                    'ID',
                    'Name_[LANG]',
                ]
            );
            
            $data->GearSet->Attributes[$id] = [
                'Attribute' => $attr,
                'Value' => $value
            ];
        }
        
        $data->GearSet->Attributes = array_values((array)$data->GearSet->Attributes);
        
        //
        // Gear Items
        //
        foreach ($data->GearSet->Gear as $slot => $gear) {
            // item
            $gear->Item = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_Item_{$gear->ID}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                    'LevelEquip',
                    'LevelItem',
                    'Rarity',
                    'ItemUICategory.ID',
                    'ItemUICategory.Name_[LANG]',
                    'ClassJobCategory.ID',
                    'ClassJobCategory.Name_[LANG]',
                ]
            );
            
            // mirage
            $gear->Mirage = $gear->Mirage ? self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_Item_{$gear->Mirage}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                ]
            ) : null;
            
            // dyes
            $gear->Dye = $gear->Dye ? self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_Item_{$gear->Dye}"),
                [
                    'ID',
                    'Icon',
                    'Name_[LANG]',
                ]
            ) : null;
            
            // materia
            foreach ($gear->Materia as $i => $materia) {
                $gear->Materia[$i] = self::extendCharacterDataHandlerSimple(
                    Redis::Cache()->get("xiv_Item_{$materia}"), [
                        'ID',
                        'Icon',
                        'Url',
                        'Name_[LANG]',
                    ]
                );
            }
            
            unset($gear->ID);
        }
    }
    
    public static function extendAchievementData($achievements)
    {
        if (!isset($achievements->List) || empty($achievements->List)) {
            return null;
        }
        
        foreach ($achievements->List as $i => $achievement) {
            $achievements->List[$i] = self::extendCharacterDataHandlerSimple(
                Redis::Cache()->get("xiv_Achievement_{$achievement->ID}"),
                [
                    "ID",
                    "Name_[LANG]",
                    "Points",
                    "Icon",
                ]
            );
            
            $achievements->List[$i]->Date = $achievement->Date;
        }
    }
}
