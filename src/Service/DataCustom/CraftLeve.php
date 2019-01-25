<?php

namespace App\Service\DataCustom;

use App\Service\Common\Arrays;
use App\Service\Content\ManualHelper;

class CraftLeve extends ManualHelper
{
    // run after "links"
    const PRIORITY = 120;
    
    public function handle()
    {
        foreach ($this->redis->get("ids_CraftLeve") as $id) {
            $key = "xiv_CraftLeve_{$id}";
            $cl  = $this->redis->get($key);
            // ---------------------------------------------------
            
            // up to 4 possible fields, from Item0, Item1, Item2 and Item3
            foreach([0,1,2,3] as $num) {
                // add defaults
                $cl->{"Item{$num}Recipes"} = [];

                // grab item from the craft leve entry
                $item = $cl->{"Item{$num}"};
                
                if (empty($item)) {
                    continue;
                }
                
                // get the full item data
                $item = $this->redis->get("xiv_Item_{$item->ID}");
                
                // grab all recipes for this item
                if (!isset($item->GameContentLinks->Recipe->ItemResult)) {
                    continue;
                }
                
                // loop through recipes that make this item
                foreach ($item->GameContentLinks->Recipe->ItemResult as $recipeId) {
                    // grab the recipe data
                    $recipe = $this->redis->get("xiv_Recipe_{$recipeId}");
                    
                    // minify it, because it too big
                    $recipe = Arrays::minification($recipe);
                    
                    // add it to craft leve
                    $cl->{"Item{$num}Recipes"}[] = $recipe;
                }
            }
    
            // ---------------------------------------------------
            $this->redis->set($key, $cl, self::REDIS_DURATION);
        }
    }
}
