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
    /** @var CompanionMarketDoc */
    private $marketDoc;
    /** @var CompanionRetainerRepository */
    private $retainerRepository;
    /** @var CompanionCharacterRepository */
    private $characterRepository;
    
    public function __construct(
        EntityManagerInterface $em,
        CompanionMarketDoc $marketDoc,
        GameData $gamedata
    ) {
        $this->em = $em;
        $this->retainerRepository = $em->getRepository(CompanionRetainer::class);
        $this->characterRepository = $em->getRepository(CompanionCharacter::class);
        $this->marketDoc = $marketDoc;
        $this->gamedata = $gamedata;
    }

    /**
     * Set the current prices for an item
     */
    public function set(MarketItem $marketItem)
    {
        $marketItem->Updated = time();
        $this->marketDoc->save($marketItem->Server, $marketItem->Item, $marketItem);
    }
    
    /**
     * Get the current prices for an item
     */
    public function get(int $server, int $itemId, bool $internal = false): ?MarketItem
    {
        /** @var MarketItem $item */
        $item = $this->marketDoc->get($server, $itemId);

        // if not internally called, append some more info
        if ($internal === false) {
            // append item information
            $item->Item = GameItem::build($item->ItemID);
            $item->IsTracked = 0;
            $item->UpdatePriority = 0;
        }

        return $item;
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
