<?php

namespace App\Service\Companion;

use App\Common\Service\ElasticSearch\ElasticQuery;
use App\Common\Service\ElasticSearch\ElasticSearch;
use App\Common\Utils\Arrays;
use App\Entity\CompanionCharacter;
use App\Entity\CompanionRetainer;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\Models\Buyer;
use App\Service\Companion\Models\Crafter;
use App\Service\Companion\Models\GameItem;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Companion\Models\Retainer;
use App\Service\Companion\Models\PriceListing;
use App\Service\Content\GameData;
use App\Common\Service\Redis\Redis;
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
    /** @var CompanionCharacterRepository */
    private $characterRepository;
    
    public function __construct(
        EntityManagerInterface $em,
        GameData $gamedata
    ) {
        $this->em = $em;
        $this->retainerRepository = $em->getRepository(CompanionRetainer::class);
        $this->characterRepository = $em->getRepository(CompanionCharacter::class);
        $this->gamedata = $gamedata;
    }
    
    public function connect()
    {
        if ($this->elastic === null) {
            $this->elastic  = new ElasticSearch('ELASTIC_SERVER_COMPANION');
        }
    }

    /**
     * Rebuilds the ElasticSearch index (this deletes everything inside the index)
     * Should only be run during the initial build of the service.
     */
    public function rebuildIndex()
    {
        $this->connect();
        $this->elastic->deleteIndex(self::INDEX);
        $this->elastic->addIndexCompanion(self::INDEX);
    }
    
    /**
     * Set the current prices for an item
     */
    public function set(MarketItem $marketItem)
    {
        $this->connect();
        $marketItem->Updated = time();
    
        $data = json_decode(json_encode($marketItem), true);
        
        try {
            $this->elastic->addDocument(self::INDEX, self::INDEX, $marketItem->ID, $data);
        } catch (\Exception $ex) {
            file_put_contents(__DIR__.'/../../../companion_put_errors.txt', $ex->getMessage() . PHP_EOL, FILE_APPEND);
        }
        
    }
    
    /**
     * Set the current prices for multiple items
     *
     * @param $marketItems MarketItem[]
     */
    public function setBulk($marketItems)
    {
        $this->connect();
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
    public function get(int $server, int $itemId, int $maxHistory = null, int $maxPrices = null, bool $internal = false): ?MarketItem
    {
        $this->connect();
        $item = new MarketItem($server, $itemId);
    
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
    
            $item->IsTracked = $itemQueue > 0;
            $item->UpdatePriority = $itemQueue;
        }
        
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
        foreach ($source['Prices'] as $i => $price) {
            // limit history
            if ($maxPrices && $i >= $maxPrices) {
                break;
            }
            
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
    
        return $item;
    }

    /**
     * Delete a document
     */
    public function delete(int $server, int $itemId)
    {
        $this->connect();
        $this->elastic->deleteDocument(self::INDEX, self::INDEX, "{$server}_{$itemId}");
    }
    
    /**
     * Get items being sold by a retainer
     */
    public function getRetainerItems(string $retainerId)
    {
        // check cache
        if ($data = Redis::Cache()->get(__METHOD__ . $retainerId)) {
            return $data;
        }
        
        /** @var CompanionRetainer $entity */
        $entity = $this->retainerRepository->find($retainerId);
        if ($entity === null) {
            throw new NotFoundHttpException();
        }
        
        
        $obj     = Retainer::build($entity);
        $results = $this->genericSearchEntry('Prices', 'RetainerID', $obj->ID);
        
        // add listings
        foreach ($results['hits']['hits'] as $hit) {
            $obj->Items[] = PriceListing::build($hit['_source'], $retainerId);
        }
        
        // cache for 5 minutes.
        Redis::Cache()->set(__METHOD__ . $retainerId, $obj, 300);
        return $obj;
    }
    
    /**
     * Get items being sold by a retainer
     */
    public function getCharacterSignatureItems(string $lodestoneId)
    {
        // check cache
        if ($data = Redis::Cache()->get(__METHOD__ . $lodestoneId)) {
            return $data;
        }
        
        /** @var CompanionCharacter $entity */
        $entity = $this->characterRepository->find($lodestoneId);
        if ($entity === null) {
            throw new NotFoundHttpException();
        }
        
        $obj     = Crafter::build($entity);
        $results = $this->genericSearchEntry('Prices', 'RetainerID', $obj->ID);
        
        // add listings
        foreach ($results['hits']['hits'] as $hit) {
            $obj->Items[] = PriceListing::build($hit['_source'], $lodestoneId);
        }
        
        // cache for 5 minutes.
        Redis::Cache()->set(__METHOD__ . $lodestoneId, $obj, 300);
        return $obj;
    }

    /**
     * Get items bought by a player
     */
    public function getCharacterHistory(string $lodestoneId)
    {
        // check cache
        if ($data = Redis::Cache()->get(__METHOD__ . $lodestoneId)) {
            return $data;
        }
        
        /** @var CompanionCharacter $entity */
        $entity = $this->characterRepository->findOneBy([ 'lodestoneId' => $lodestoneId ]);
        if ($entity === null) {
            throw new NotFoundHttpException();
        }
    
        $obj     = Buyer::build($entity);
        $results = $this->genericSearchEntry('History', 'CharacterID', $obj->ID);
        
        // add history
        foreach ($results['hits']['hits'] as $hit) {
            $obj->addHistory($hit['_source']);
        }
    
        // order
        Arrays::sortBySubKey($obj->History, 'PurchaseDate');
    
        // cache for 5 minutes.
        Redis::Cache()->set(__METHOD__ . $lodestoneId, $obj, 300);
        return $obj;
    }
    
    /**
     * Generic search handler
     */
    private function genericSearchEntry(string $nest, string $field, $value, $limit = 500)
    {
        $this->connect();
    
        /**
         * Build retainer query, limit to 30 results.
         */
        $query1 = new ElasticQuery();
        $query1->queryMatchPhrase("{$nest}.{$field}", $value);
        
        $query2 = new ElasticQuery();
        $query2->nested($nest, $query1->getQuery());
        $query2->limit(0, $limit);
        
        return $this->elastic->search(self::INDEX, self::INDEX, $query2->getQuery());
    }
    
    /**
     * Perform searches
     */
    public function search()
    {
        $this->connect();
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
    
    /**
     * Get all companion items
     */
    public function getTrackedItems()
    {
        $sql = "SELECT DISTINCT(item), normal_queue, state FROM companion_market_items";
        $sql = $this->em->getConnection()->prepare($sql);
        $sql->execute();
        
        return $sql->fetchAll();
    }
}
