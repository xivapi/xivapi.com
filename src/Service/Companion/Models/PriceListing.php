<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class PriceListing
{
    public $Item;
    public $Prices;
    public $Updated;
    
    /**
     * Build a RetainerListing from an ElasticSearch 'source'.
     */
    public static function build(array $source, string $retainerId): self
    {
        $listing            = new PriceListing();
        $listing->Item      = GameItem::build($source['ItemID']);
        $listing->Updated   = $source['Updated'];
        $listing->UpdatedMS = (int)($source['Updated'] * 1000);
    
        // grab the prices
        foreach ($source['Prices'] as $price) {
            if ($price['RetainerID'] == $retainerId) {
                $marketListing = new MarketListing();
            
                // these fields map 1:1
                foreach ($price as $key => $value) {
                    $marketListing->{$key} = $value;
                }
            
                $listing->Prices[] = $marketListing;
            }
        }
        
        return $listing;
    }
}
