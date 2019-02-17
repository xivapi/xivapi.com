<?php

namespace App\Service\Companion;

use App\Service\Common\Arrays;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameData;
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
    
    public function __construct(GameData $gamedata)
    {
        $this->elastic  = new ElasticSearch('ELASTIC_SERVER_COMPANION');
        $this->gamedata = $gamedata;
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
        $this->elastic->addDocument(self::INDEX, self::INDEX, $marketItem->ID, $data);
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
            $documents[$marketItem->ID] = json_decode(json_encode($marketItem), true);
        }
        
        $this->elastic->bulkDocuments(self::INDEX, self::INDEX, $documents);
    }
    
    /**
     * Get the current prices for an item
     */
    public function get(int $server, int $itemId, int $maxHistory = null): ?MarketItem
    {
        try {
            $result = $this->elastic->getDocument(self::INDEX, self::INDEX, "{$server}_{$itemId}");
        } catch (\Exception $ex) {
            return null;
        }
        
        $source = $result['_source'];
        $item   = new MarketItem($source['Server'], $source['ItemID'], $source['Updated']);
        
        // sort results
        Arrays::sortBySubKey($source['Prices'], 'PricePerUnit', true);
        Arrays::sortBySubKey($source['History'], 'PurchaseDate');
        
        // map out current prices
        foreach ($source['Prices'] as $price) {
            $obj = new MarketListing();

            // these fields map 1:1
            foreach($price as $key => $value) {
                $obj->{$key} = $value;
            }
            
            $item->Prices[] = $obj;
        }
        
        // map out historic prices
        foreach ($source['History'] as $i => $price) {
            // limit history
            if ($maxHistory && $i >= $maxHistory) {
                break;
            }
            
            $obj = new MarketHistory();
        
            // these fields map 1:1
            foreach($price as $key => $value) {
                $obj->{$key} = $value;
            }
        
            $item->History[] = $obj;
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
}
