<?php

namespace App\Service\Companion;

use App\Service\Redis\Redis;
use Companion\CompanionApi;

class Companion
{
    /**
     * Grab
     */
    private function getCompanionApi($server): CompanionApi
    {
        return new CompanionApi("xivapi_{$server}", CompanionTokenManager::PROFILE_FILENAME);
    }
    
    /**
     * Get prices for a specific item
     */
    public function getItemPrices(string $server, int $itemId)
    {
        return $this->getCompanionApi($server)->Market()->getItemMarketListings($itemId);
    }
    
    /**
     * Get history for a specific item
     */
    public function getItemHistory(string $server, int $itemId)
    {
        return $this->getCompanionApi($server)->Market()->getTransactionHistory($itemId);
    }
    
    /**
     * Get category listings for an item
     */
    public function getCategoryList(string $server, int $categoryId)
    {
        return $this->getCompanionApi($server)->Market()->getMarketListingsByCategory($categoryId);
    }
    
    /**
     * Get a list of search categories
     */
    public function getCategories(): array
    {
        $arr = [];
        foreach (Redis::Cache()->get('ids_ItemSearchCategory') as $id) {
            $category = Redis::Cache()->get("xiv_ItemSearchCategory_{$id}");
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
