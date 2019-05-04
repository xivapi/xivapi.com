<?php

namespace App\Service\Companion;

use App\Entity\CompanionRetainer;
use App\Repository\CompanionRetainerRepository;
use App\Service\Common\Arrays;
use App\Service\Companion\Models\GameItem;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Companion\Models\Retainer;
use App\Service\Companion\Models\RetainerListing;
use App\Service\Content\GameData;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use App\Service\SearchElastic\ElasticQuery;
use App\Service\SearchElastic\ElasticSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles the Elastic Search Companion Market info
 */
class CompanionMarket
{
    const INDEX = 'companion';

    /** @var EntityManagerInterface */
    private $em;
    /** @var ElasticSearch */
    private $elastic;
    /** @var CompanionRetainerRepository */
    private $retainerRepository;
    
    public function __construct(
        EntityManagerInterface $em,
        GameData $gamedata
    ) {
        $this->em = $em;
        $this->retainerRepository = $em->getRepository(CompanionRetainer::class);
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
        $marketItem->Updated = time();
    
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
            $marketItem->Updated = time();
            $documents[$marketItem->ID] = json_decode(json_encode($marketItem), true);
        }
        
        $this->elastic->bulkDocuments(self::INDEX, self::INDEX, $documents);
    }
    
    /**
     * Get the current prices for an item
     */
    public function get(int $server, int $itemId, int $maxHistory = null, bool $internal = false): ?MarketItem
    {
        $item = new MarketItem($server, $itemId);
        
        try {
            $result = $this->elastic->getDocument(self::INDEX, self::INDEX, "{$server}_{$itemId}");
        } catch (\Exception $ex) {
            return $item;
        }

        // just incase...
        if ($result === null) {
            return $item;
        }
    
        // grab document source
        $source = $result['_source'];
    
        // set some basic info
        $item->Updated = $source['Updated'];
        $item->LodestoneID = $source['LodestoneID'];
    
        // map out current prices
        foreach ($source['Prices'] as $price) {
            $obj = new MarketListing();
        
            // these fields map 1:1
            foreach ($price as $key => $value) {
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
            foreach ($price as $key => $value) {
                $obj->{$key} = $value;
            }
        
            $item->History[] = $obj;
        }
    
        // if not internally called, append some more info
        if ($internal === false) {
            // append item information
            $item->Item = GameItem::build($item->ItemID);

            $key = __METHOD__ . $item->ID;
            if (!$itemQueue = Redis::Cache()->get($key)) {
                $sql = "SELECT normal_queue FROM companion_market_items WHERE item = {$item->ItemID} AND server = {$item->Server} LIMIT 1";
                $stmt = $this->em->getConnection()->prepare($sql);
                $stmt->execute();
                $itemQueue = $stmt->fetch()['normal_queue'] ?? null;

                // cache for an hour
                Redis::Cache()->set($key, $itemQueue);
            }

            $item->UpdatePriority = $itemQueue;


        }
    
        return $item;
    }
    
    /**
     * Get items being sold by a retainer
     */
    public function retainerItems(string $retainerId)
    {
        // if the retainer is not in the database, it doesn't exist.
        /** @var CompanionRetainer $companionRetainer */
        $companionRetainer = $this->retainerRepository->find($retainerId);
        if ($companionRetainer === null) {
            throw new NotFoundHttpException();
        }
        
        // check cache
        if ($data = Redis::Cache()->get(__METHOD__ . $retainerId)) {
            return $data;
        }
    
        /**
         * Setup a new retainer
         */
        $retainer = new Retainer();
        $retainer->ID     = $retainerId;
        $retainer->Name   = $companionRetainer->getName();
        $retainer->Server = GameServers::LIST[$companionRetainer->getServer()];
        
        /**
         * Build retainer query, limit to 30 results.
         */
        $query1 = new ElasticQuery();
        $query1->queryMatchPhrase('Prices.RetainerID', $retainerId);
        $query2 = new ElasticQuery();
        $query2->nested('Prices', $query1->getQuery());
        $query2->limit(0, 30);
        $results = $this->elastic->search(self::INDEX, self::INDEX, $query2->getQuery());
    
        /**
         * Build retainer store
         */
        foreach ($results['hits']['hits'] as $hit) {
            $retainer->Items[] = RetainerListing::build($hit['_source'], $retainerId);
        }
        
        // cache for 5 minutes.
        Redis::Cache()->set(__METHOD__ . $retainerId, $retainer, 300);
        return $retainer;
    }
    
    /**
     * Perform searches
     */
    public function search()
    {
        $query1 = new ElasticQuery();
        $query1->queryMatch('Prices.RetainerID', 'f935eac3-6560-4a4e-b07b-84ba4b37d60a');
        #$query1->filterRange('Prices.PricePerUnit', 50000, 'gte');
        
        $query2 = new ElasticQuery();
        $query2->nested('Prices', $query1->getQuery());
        $query2->limit(0, 2);
        $query2->sort([
            [ 'Prices.PricePerUnit', 'desc' ]
        ]);
        
        $results = $this->elastic->search(self::INDEX, self::INDEX, $query2->getQuery());
        
        print_r($results);
        die;
    }
}
