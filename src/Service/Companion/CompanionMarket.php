<?php

namespace App\Service\Companion;

use App\Exception\CompanionMarketItemException;
use App\Exception\CompanionMarketServerException;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\SearchElastic\ElasticQuery;
use App\Service\SearchElastic\ElasticSearch;

/**
 * Handles the Elastic Search Companion Market info
 */
class CompanionMarket
{
    const INDEX = 'companion';
    
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
    public function rebuildIndex()
    {
        $this->elastic->deleteIndex(self::INDEX);
        $this->elastic->addIndexCompanion(self::INDEX);
    }
    
    /**
     * Set the current prices for an item
     */
    public function set(MarketItem $marketItem)
    {
        $data = json_decode(json_encode($marketItem), true);
        $this->elastic->addDocument(self::INDEX, self::INDEX, $marketItem->id, $data);
    }
    
    /**
     * Set the current prices for multiple items
     *
     * @param $marketItems MarketItem[]
     */
    public function setBulk($marketItems)
    {
        $documents = [];
        foreach ($marketItems as $i => $marketItem) {
            $documents[$marketItem->id] = json_decode(json_encode($marketItem), true);
        }
        
        $this->elastic->bulkDocuments(self::INDEX, self::INDEX, $documents);
    }
    
    /**
     * Get the current prices for an item
     */
    public function get(string $server, int $itemId): MarketItem
    {
        $server = $this->getServer($server);
        $result = $this->elastic->getDocument(self::INDEX, self::INDEX, "{$server}_{$itemId}");
        
        if ($result['found'] === 0) {
            throw new CompanionMarketItemException();
        }
        
        $item = new MarketItem();
        $item->id      = $result['_source']['id'];
        $item->server  = $result['_source']['server'];
        $item->item_id = $result['_source']['item_id'];
        $item->prices  = [];
        $item->history = [];
        
        // map out current prices
        foreach ($result['_source']['prices'] as $price) {
            $obj = new MarketListing();

            // these fields map 1:1
            foreach($price as $key => $value) {
                $obj->{$key} = $value;
            }
            
            $item->prices[] = $obj;
        }
    
        // map out historic prices
        foreach ($result['_source']['history'] as $price) {
            $obj = new MarketHistory();
        
            // these fields map 1:1
            foreach($price as $key => $value) {
                $obj->{$key} = $value;
            }
        
            $item->history[] = $obj;
        }
        
        return $item;
    }
    
    /**
     * Perform searches
     */
    public function search()
    {
        $query1 = new ElasticQuery();
        $query1->filterTerm('prices.retainer_id', 69926);
        #$query1->filterRange('prices.price_total', 9900000, 'gte');
        
        $query2 = new ElasticQuery();
        $query2->nested('prices', $query1->getQuery());
        $query2->limit(0, 2);
        $query2->sort([
            [ 'prices.price_total', 'desc' ]
        ]);
        
        $results = $this->elastic->search(self::INDEX, self::INDEX, $query2->getQuery());
        
        print_r($results);
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
