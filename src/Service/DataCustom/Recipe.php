<?php

namespace App\Service\DataCustom;

use App\Service\Common\Arrays;
use App\Service\Content\ManualHelper;

class Recipe extends ManualHelper
{
    const PRIORITY = 15;
    
    private $itemToRecipe = [];
    
    public function handle()
    {
        $this->warmRecipeData();
        
        $ids = $this->getContentIds('Recipe');
        foreach ($ids as $id) {
            $key = "xiv_Recipe_{$id}";
            $recipe = $this->redis->get($key);
            
            $recipe->ClassJob = null;
        
            // set stuff
            $this->setRecipeNames($recipe);
            $this->setIngredientRecipes($recipe);
            $this->setClassJob($recipe);
            
            // save
            $this->redis->set($key, $recipe, self::REDIS_DURATION);
        }
    }
    
    private function warmRecipeData()
    {
        // Build a list of ItemIds to RecipeIds
        foreach ($this->redis->get('ids_Recipe') as $id) {
            $recipe = $this->redis->get("xiv_Recipe_{$id}");
            
            if (!$recipe->ItemResult) {
                continue;
            }
    
            $recipe->ClassJob = $recipe->ClassJob ?? null;
            $this->itemToRecipe[$recipe->ItemResult->ID][] = $recipe;
        }
    }
    
    /**
     * Add the recipe names to the root
     */
    private function setRecipeNames($recipe)
    {
        $recipe->Name_en = $recipe->ItemResult->Name_en ?? null;
        $recipe->Name_de = $recipe->ItemResult->Name_de ?? null;
        $recipe->Name_fr = $recipe->ItemResult->Name_fr ?? null;
        $recipe->Name_ja = $recipe->ItemResult->Name_ja ?? null;
    }
    
    /**
     * Add ingredients
     */
    private function setIngredientRecipes($recipe)
    {
        foreach (range(0, 9) as $i) {
            $ingredient = $recipe->{"ItemIngredient{$i}"};
            $ingredientKey = "ItemIngredientRecipe{$i}";
            
            $recipe->{$ingredientKey} = null;
            
            if (!isset($ingredient->ID)) {
                continue;
            }
            
            $recipe->{$ingredientKey} = $this->itemToRecipe[$ingredient->ID] ?? null;
        }
    }
    
    /**
     * Set class job
     */
    private function setClassJob($recipe)
    {
        $arr = [
            0 => 8,
            1 => 9,
            2 => 10,
            3 => 11,
            4 => 12,
            5 => 13,
            6 => 14,
            7 => 15,
        ];
        
        //
        // Set on main recipe
        //
        if (isset($recipe->CraftType->ID)) {
            $recipe->ClassJob = Arrays::minification(
                $this->redis->get("xiv_ClassJob_{$arr[(int)$recipe->CraftType->ID]}")
            );
        }
        
        //
        // Set class job on each recipe ingredient
        //
        foreach (range(0, 9) as $i) {
            $column = "ItemIngredientRecipe{$i}";
            
            if ($recipe->{$column}) {
                foreach ($recipe->{$column} as $subRecipe) {
                    if (isset($subRecipe->CraftType->ID)) {
                        $subRecipe->ClassJob = Arrays::minification(
                            $this->redis->get("xiv_ClassJob_{$arr[(int)$subRecipe->CraftType->ID]}")
                        );
                    }
                }
            }
        }
    }
}
