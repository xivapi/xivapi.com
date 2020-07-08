<?php

namespace App\Service\LodestoneQueue;

class CharacterConverter
{
    /**
     * Convert lodestone data into a simpler format for easier lookup
     */
    public static function convert($string)
    {
        $string = preg_replace("/[^A-Za-z0-9]/", '', $string);
        $string = str_ireplace(' ', '_', $string);
        $string = strtolower($string);
        return $string ?: '___NULL___';
    }
    
    public static function handle($data)
    {
        if ($data == null) {
            return;
        }
        
        //
        // ActiveClassJob
        //
        if ($data->ActiveClassJob) {
            unset($data->ActiveClassJob->ClassName);
            unset($data->ActiveClassJob->JobName);
        }

    
        //
        // Misc
        //
        $data->Gender        = $data->Gender == 'male' ? 1 : 2;
        $data->Town          = CharacterData::find('Town', $data->Town->Name);
        $data->GuardianDeity = CharacterData::find('GuardianDeity', $data->GuardianDeity->Name);
        $data->Race          = CharacterData::find('Race', $data->Race);
        $data->Tribe         = CharacterData::find('Tribe', $data->Tribe);
        $data->Title         = CharacterData::find('Title', $data->Title);
    
        //
        // Build gearset
        //
        $set = new \stdClass();
        $set->GearKey    = $data->ActiveClassJob ? "{$data->ActiveClassJob->ClassID}_{$data->ActiveClassJob->JobID}" : '';
        $set->ClassID    = $data->ActiveClassJob->ClassID ?? null;
        $set->JobID      = $data->ActiveClassJob->JobID ?? null;
        $set->Level      = $data->ActiveClassJob->Level ?? null;

        $set->Gear       = new \stdClass();
        $set->Attributes = [];
    
        //
        // Attributes
        //
        foreach ($data->GearSet['Attributes'] as $attr) {
            $attr->Name = ($attr->Name === 'Critical Hit Rate') ? 'Critical Hit' : $attr->Name;
            $set->Attributes[CharacterData::find('BaseParam', $attr->Name)] = $attr->Value;
        }
    
        //
        // Gear
        //
        foreach ($data->GearSet['Gear'] as $slot => $item) {
            $item->ID = CharacterData::find('Item', $item->Name);
        
            // has dye?
            if (isset($item->Dye) && $item->Dye) {
                $item->Dye = CharacterData::find('Item', $item->Dye->Name);
            }
        
            // has mirage?
            if (isset($item->Mirage) && $item->Mirage) {
                $item->Mirage = CharacterData::find('Item', $item->Mirage->Name);
            }
        
            // has materia?
            if (isset($item->Materia) && $item->Materia) {
                foreach ($item->Materia as $m => $materia) {
                    $item->Materia[$m] = CharacterData::find('Item', $materia->Name);
                }
            }
        
            // don't need these
            unset($item->Slot);
            unset($item->Name);
            unset($item->Category);
        
            $set->Gear->{$slot} = $item;
        }
    
        $data->GearSet = $set;
    
        //
        // ClassJobs
        //
        if ($data->ClassJobs) {
            foreach ($data->ClassJobs as $classJob) {
                unset($classJob->ClassName);
                unset($classJob->JobName);
            }
        }

        //
        // Grand Company
        //
        if (isset($data->GrandCompany->Name)) {
            $town = [
                'Maelstrom' => 'GCRankLimsa',
                'Order of the Twin Adder' => 'GCRankGridania',
                'Immortal Flames' => 'GCRankUldah',
            ];
        
            $townSelected   = $town[$data->GrandCompany->Name];
            $genderSelected = $data->Gender == 1 ? 'Male' : 'Female';
            $rankDataSet    = "{$townSelected}{$genderSelected}Text";
        
            $data->GrandCompany->NameID = CharacterData::find('GrandCompany', $data->GrandCompany->Name);
            $data->GrandCompany->RankID = CharacterData::find($rankDataSet, $data->GrandCompany->Rank);
        
            unset($data->GrandCompany->Name);
            unset($data->GrandCompany->Rank);
            unset($data->GrandCompany->Icon);
        }
    }
    
    /**
     * This isn't in use but could be good to reduce payload
     */
    public static function handleMinionMounts($data)
    {
        /*
        //
        // Minions and Mounts
        //
        foreach ($data->Minions as $m => $minion) {
            $data->Minions[$m] = CharacterData::find('Companion', $minion->Name);
        
            // add all minions of light
            if (in_array($data->Minions[$m], [67,68,69,70])) {
                $data->Minions[] = 67;
                $data->Minions[] = 68;
                $data->Minions[] = 69;
                $data->Minions[] = 70;
            }
        
            // add all wind up leaders
            if (in_array($data->Minions[$m], [71,72,73,74])) {
                $data->Minions[] = 71;
                $data->Minions[] = 72;
                $data->Minions[] = 73;
                $data->Minions[] = 74;
            }
        }
    
        foreach ($data->Mounts as $m => $mount) {
            $data->Mounts[$m] = CharacterData::find('Mount', $mount->Name);
        }
    
        $data->Minions = array_values(array_unique($data->Minions));
        $data->Mounts = array_values(array_unique($data->Mounts));
        */
    }
}
