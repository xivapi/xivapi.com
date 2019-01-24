<?php

namespace App\Service\Companion;

use Companion\CompanionApi;

class Companion
{
    use CompanionEnrich;
    
    const PROFILE_FILENAME = __DIR__.'/accounts.json';
    
    /** @var CompanionApi */
    private $api;
    
    /**
     * Set server for all DC requests
     */
    public function setServer(string $server): Companion
    {
        $server = ucwords($server);
        
        // initialize api
        $this->api = new CompanionApi("xivapi_{$server}", self::PROFILE_FILENAME);
    
        // validate
        $validServers = CompanionTokenManager::SERVERS;
        if (!isset($validServers[$server])) {
            throw new \Exception("Sorry! {$server} is not a valid server.");
        }
        
        // if no token, error
        if (empty($this->api->Profile()->getToken())) {
            throw new \Exception("Issue fetching data from Sight API for server: {$server} - This could be due to world congestion, maintenance or service issues. Please jump on Discord for support.");
        }
        
        return $this;
    }
    
    /**
     * Get prices for a specific item
     */
    public function getItemPrices($itemId): array
    {
        $item     = $this->getEnrichedItem($itemId);
        $response = $this->api->Market()->getItemMarketListings($itemId);
        
        // build prices
        $prices = [];
        foreach ($response->entries as $row) {
            $prices[] = [
                'ID'             => $itemId,
                'Materia'        => $this->getEnrichedMateria($row->materia),
                'Town'           => $this->getEnrichedTown($row->registerTown),
                'Quantity'       => $row->stack,
                'CraftSignature' => $row->signatureName,
                'IsHQ'           => (bool)($row->hq ? true : false),
                'IsCrafted'      => (bool)($row->isCrafted ? true : false),
                'Stain'          => $row->stain,
                'PricePerUnit'   => $row->sellPrice,
                'PriceTotal'     => $row->sellPrice * $row->stack,
                'RetainerName'   => $row->sellRetainerName,
            ];
        }
        
        // build market
        return [
            'Item'      => $item,
            'Prices'    => $prices,
            'Lodestone' => [
                'LodestoneId'   => $response->eorzeadbItemId,
                'Icon'          => $response->icon,
                'IconHq'        => $response->iconHq,
            ],
        ];
    }
    
    /**
     * Get history for a specific item
     */
    public function getItemHistory($itemId): array
    {
        $item     = $this->getEnrichedItem($itemId);
        $response = $this->api->Market()->getTransactionHistory($itemId);
        
        // build history
        $history = [];
        foreach ($response->history as $row) {
            $history[] = [
                'Quantity'      => $row->stack,
                'PricePerUnit'  => $row->sellPrice,
                'PriceTotal'    => $row->sellPrice * $row->stack,
                'CharacterName' => $row->buyCharacterName,
                'PurchaseDate'  => $row->buyRealDate/1000,
                'IsHQ'          => $row->hq,
            ];
        }
    
        // build market
        return [
            'Item'      => $item,
            'History'   => $history,
        ];
    }
    
    /**
     * Get category listings for an item
     */
    public function getCategoryList($categoryId): array
    {
        $response = $this->api->Market()->getMarketListingsByCategory($categoryId);
        
        // build list
        $list = [];
        foreach ($response->items as $row) {
            $list[] = [
                'ID'        => $row->catalogId,
                'Item'      => $this->getEnrichedItem($row->catalogId),
                'Quantity'  => $row->count,
            ];
        }
        
        return $list;
    }
    
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
