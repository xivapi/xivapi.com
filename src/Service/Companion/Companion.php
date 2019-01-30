<?php

namespace App\Service\Companion;

class Companion
{
    /**
     * Get a list of search categories
     */
    public function getCategories(): array
    {
        $arr = [];
        foreach ($this->cache->get('ids_ItemSearchCategory') as $id) {
            $category = $this->cache->get("xiv_ItemSearchCategory_{$id}");
            // Ignore anything with no name or no category id
            if (empty($category->Name_en) || $category->Category == 0) {
                continue;
            }
        
            // Don't care much about these bits
            unset($category->ClassJob, $category->GameContentLinks);
            $arr[] = $category;
        }
        
        return $arr;
    }
}
