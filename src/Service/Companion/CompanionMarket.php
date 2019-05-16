<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Common\Service\ElasticSearch\ElasticQuery;
use App\Common\Service\ElasticSearch\ElasticSearch;
use App\Entity\CompanionCharacter;
use App\Entity\CompanionRetainer;
use App\Repository\CompanionCharacterRepository;
use App\Repository\CompanionRetainerRepository;
use App\Service\Companion\Models\Buyer;
use App\Service\Companion\Models\GameItem;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Companion\Models\Retainer;
use App\Service\Companion\Models\RetainerListing;
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
        $this->elastic->addDocument(self::INDEX, self::INDEX, $marketItem->ID, $data);
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
    public function get(int $server, int $itemId, int $maxHistory = null, bool $internal = false): ?MarketItem
    {
        $this->connect();
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
        $this->connect();
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
     * Get items bought by a player
     */
    public function buyerItems(string $lodestoneId)
    {
        $this->connect();

        // if the retainer is not in the database, it doesn't exist.
        /** @var CompanionCharacter $character */
        $character = $this->characterRepository->findOneBy([ 'lodestoneId' => $lodestoneId ]);
        if ($character === null) {
            throw new NotFoundHttpException();
        }

        // check cache
        if ($data = Redis::Cache()->get(__METHOD__ . $lodestoneId)) {
            //return $data;
        }

        /**
         * Setup a new retainer
         */
        $buyer = Buyer::build($character);

        /**
         * Build retainer query, limit to 30 results.
         */
        $query1 = new ElasticQuery();
        $query1->queryMatchPhrase('History.CharacterID', $character->getId());
        $query2 = new ElasticQuery();
        $query2->nested('History', $query1->getQuery());
        $query2->limit(0, 500);
        $results = $this->elastic->search(self::INDEX, self::INDEX, $query2->getQuery());

        /**
         * Build retainer store
         */
        foreach ($results['hits']['hits'] as $hit) {
            $buyer->addHistory($hit['_source']);
        }

        // cache for 5 minutes.
        Redis::Cache()->set(__METHOD__ . $lodestoneId, $buyer, 300);
        return $buyer;
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
}
