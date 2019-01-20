<?php

namespace App\Service\Companion;

use App\Exception\CompanionMarketItemException;
use App\Exception\CompanionMarketServerException;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketItemListing;
use App\Service\Content\GameServers;
use App\Service\SearchElastic\ElasticSearch;

/**
 * Handles the Elastic Search Companion Market info
 */
class CompanionMarket
{
    const CURRENT = 'companion_prices';
    const HISTORY = 'companion_history';
    
    /** @var ElasticSearch */
    private $elastic;
    
    public function __construct()
    {
        [$ip, $port] = explode(',', getenv('ELASTIC_SERVER_COMPANION'));
        $this->elastic = new ElasticSearch($ip, $port);
    }
    
    /**
     * Rebuilds the ElasticSearch index (this deletes everything inside the index)
     * Should only be run during the initial build of the service.
     */
    public function rebuildIndex($index)
    {
        $this->elastic->deleteIndex($index);
        $this->elastic->addIndex($index);
    }
    
    /**
     * Set the current prices for an item
     */
    public function setPrices(MarketItem $marketItem)
    {
        $data = json_decode(json_encode($marketItem), true);
        $this->elastic->addDocument(self::CURRENT, self::CURRENT, $marketItem->id, $data);
    }
    
    /**
     * Set the current prices for an item
     */
    public function setHistory(MarketItem $marketItem)
    {
        $data = json_decode(json_encode($marketItem), true);
        $this->elastic->addDocument(self::HISTORY, self::HISTORY, $marketItem->id, $data);
    }
    
    /**
     * Set the current prices for multiple items
     *
     * @param $marketItems MarketItem[]
     */
    public function setPricesBulk($marketItems)
    {
        $documents = [];
        foreach ($marketItems as $i => $marketItem) {
            $documents[$marketItem->id] = json_decode(json_encode($marketItem), true);
        }
        
        $this->elastic->bulkDocuments(self::CURRENT, self::CURRENT, $documents);
    }
    
    /**
     * Set the current historic prices for multiple items
     *
     * @param $marketHistoryListings MarketHistory[]
     */
    public function setHistoryBulk($marketHistoryListings)
    {
        $documents = [];
        foreach ($marketHistoryListings as $i => $marketHistory) {
            $documents[$marketHistory->id] = json_decode(json_encode($marketHistory), true);
        }
    
        $this->elastic->bulkDocuments(self::HISTORY, self::HISTORY, $documents);
    }
    
    /**
     * Get the current prices for an item
     */
    public function getPrices(string $server, int $itemId): MarketItem
    {
        $server = $this->getServer($server);
        $result = $this->elastic->getDocument(self::CURRENT, self::CURRENT, "{$server}_{$itemId}");
        
        if ($result['found'] === 0) {
            throw new CompanionMarketItemException();
        }
        
        $item = new MarketItem();
        $item->id      = $result['_source']['id'];
        $item->server  = $result['_source']['server'];
        $item->item_id = $result['_source']['item_id'];
        $item->sales   = $result['_source']['sales'];
        $item->prices  = [];
        
        foreach ($result['_source']['prices'] as $price) {
            $listing = new MarketItemListing();

            // these fields map 1:1
            foreach($price as $key => $value) {
                $listing->{$key} = $value;
            }
            
            $item->prices[] = $listing;
        }
        
        return $item;
    }
    
    /**
     * Get the history for an item
     */
    public function getHistory(string $server, int $itemId)
    {
        $server = $this->getServer($server);
        $result = $this->elastic->getDocument(self::CURRENT, self::CURRENT, "{$server}_{$itemId}");
        
        print_r($result);
        die;
    }
    
    /**
     * Get a server id from a server string
     */
    private function getServer(string $server): int
    {
        $index = array_search(ucwords($server), GameServers::LIST);
        
        if ($index === false) {
            throw new CompanionMarketServerException();
        }
        
        return $index;
    }
}
