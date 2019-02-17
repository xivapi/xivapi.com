<?php

namespace App\Service\Companion;

use App\Service\Redis\Redis;
use Companion\CompanionApi;

class Companion
{
    /** @var CompanionApi */
    private $api;
    
    public function setCompanionApiToken(\stdClass $token): self
    {
        $this->api = new CompanionApi($token);
        return $this;
    }
    
    /**
     * Get prices for a specific item
     */
    public function getItemPrices(int $itemId)
    {
        return $this->api->Market()->getItemMarketListings($itemId);
    }
    
    /**
     * Get history for a specific item
     */
    public function getItemHistory(int $itemId)
    {
        return $this->api->Market()->getTransactionHistory($itemId);
    }
    
    /**
     * Get category listings for an item
     */
    public function getCategoryList(int $categoryId)
    {
        return $this->api->Market()->getMarketListingsByCategory($categoryId);
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
