<?php

namespace App\Service\Search;

class SearchContent
{
    const LIST_DEFAULT = [
        'Achievement', 'Title',
        'Action', 'CraftAction', 'Trait', 'PvPAction', 'PvPTrait', 'Status',
        'BNpcName', 'ENpcResident',
        'Companion', 'Mount',
        'Leve',
        'Emote',
        'InstanceContent',
        'Item', 'Recipe',
        'Fate',
        'Quest',
    ];
    
    const LIST = [
        'Achievement', 'Title',
        'Action', 'CraftAction', 'Trait', 'PvPAction', 'PvPTrait', 'Status',
        'BNpcName', 'ENpcResident',
        'Companion', 'Mount',
        'Leve',
        'Emote',
        'InstanceContent',
        'Item', 'Recipe',
        'Fate',
        'Quest',
        'ContentFinderCondition',
        
        // non default
        'Balloon',
        'BuddyEquip',
        'Orchestrion',
        'PlaceName',
        'Weather',
        'World',
	    'Map',
        
        // allowed
        'lore_finder',
    ];
    
    /**
     * Validate a list of ElasticSearch indexes
     */
    public static function validate($list)
    {
        $valid = array_map('strtolower', self::LIST);
        
        foreach ($list as $i => $index) {
            if (!in_array($index, $valid)) {
                unset($list[$i]);
            }
        }
        
        return $list;
    }
}
