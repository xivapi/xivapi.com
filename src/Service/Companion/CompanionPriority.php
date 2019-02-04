<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItem;
use App\Repository\CompanionMarketItemRepository;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionPriority
{
    const SERVER = 'Phoenix';
    const CACHE_MARKET_ITEM_IDS = __DIR__.'/CompanionPriority_MarketItemIds.json';
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarketItemRepository */
    private $repository;
    /** @var Companion */
    private $companion;

    public function __construct(EntityManagerInterface $em, Companion $companion)
    {
        $this->em           = $em;
        $this->companion    = $companion;
        $this->repository   = $this->em->getRepository(CompanionMarketItem::class);
        $this->console      = new ConsoleOutput();
    }
    
    /**
     * Fetch the history for an item and work out the average
     */
    public function fetchLatestHistory(?bool $skip = false, ?int $itemId = null)
    {
        $this->console->writeln('Fetching item historic values');
        $this->console->writeln('-----------------------------');
        
        // grab item ids
        $ids   = $this->getMarketItems();
        $total = count($ids);
        
        $section = $this->console->section();
        $section->writeln("Processing item priority for: {$total} items");
        
        foreach ($ids as $i => $id) {
            // if we're just doing 1 item, skip ones we havent set
            if ($itemId && $itemId != $id) {
                continue;
            }
            
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            $lead = "[{$i} / {$total} :: {$id} :: {$item->Name_en}]";
            
            // grab market item
            $obj = $this->repository->findOneBy(['item' => $id]) ?: new CompanionMarketItem();
            
            // skip if already done (and told to do so
            if ($skip && $obj->getUpdated()) {
                continue;
            }
            
            $obj->setUpdated(time())
                ->setItem($id)
                ->setItemSearchCategory($item->ItemSearchCategory->ID);
            
            // get market history
            $section->overwrite("{$lead} Getting purchase history ...");
            $response = $this->companion->getItemHistory(self::SERVER, $id);
            
            // set history count
            $obj->setHistoryCount(count($response->history));
            
            // if it has no history, continue
            if (empty($response->history)) {
                $section->overwrite("{$lead} No sale history.");
                $obj->setHasSaleHistory(false);
                $this->em->persist($obj);
                continue;
            }
            
            $avgPurchaseDuration = [];
            $avgPurchasePrice    = [];
            $avgPurchasePriceHq  = [];
            $lastPurchaseDate    = 0;
            
            foreach ($response->history as $row) {
                // reduce purchase time down to seconds
                $row->buyRealDate = round($row->buyRealDate / 1000, 0);
                
                // add avg price
                $row->hq
                    ? $avgPurchasePriceHq[] = $row->sellPrice
                    : $avgPurchasePrice[] = $row->sellPrice;
                
                // if no $last time, just set it and move on
                if ($lastPurchaseDate === 0) {
                    $lastPurchaseDate = $row->buyRealDate;
                    $obj->setLastSaleDate($row->buyRealDate);
                    continue;
                }

                // work out time from next sale
                $difference = $lastPurchaseDate - $row->buyRealDate;
                
                // ignore time differences of 0
                if ($difference == 0) {
                    continue;
                }
                
                $avgPurchaseDuration[] = $difference;
                
                // update last time
                $lastPurchaseDate = $row->buyRealDate;
            }
            
            $obj->setAvgSaleDuration(
                empty($avgPurchaseDuration) ? 0 : round(array_sum($avgPurchaseDuration) / count($avgPurchaseDuration))
            );
    
            $obj->setAvgSalePrice(
                empty($avgPurchasePrice) ? 0 : round(array_sum($avgPurchasePrice) / count($avgPurchasePrice))
            );
    
            $obj->setAvgSalePriceHq(
                empty($avgPurchasePriceHq) ? 0 : round(array_sum($avgPurchasePriceHq) / count($avgPurchasePriceHq))
            );
    
            $this->em->persist($obj);
            
            if ($i % 50 === 0) {
                $section->overwrite("{$lead} Saving!");
                $this->em->flush();
                $this->em->clear();
                sleep(2);
            }
        }
    
        $this->em->flush();
        $section->writeln('Done!');
    }
    
    /**
     * Calculate the priority value for an item
     */
    public function calculatePriorityValues(?int $itemId = null)
    {
        $this->console->writeln('Calculating Companion Item Priority');
        $this->console->writeln('-----------------------------------');
        
        $items = $this->repository->findAll();
        
        // priority is based on seconds
        // default is 99
        $priority = [
            // 30 minutes
            1800 => 10,
            
            // 1 hour
            3600 => 11,
            
            // 4 hours
            14400 => 12,
            
            // 6 hours
            21600 => 13,
            
            // 12 hours
            43200 => 14,
            
            // 18 hours
            64800 => 15,
            
            // 24 hours
            86400 => 16,
            
            // 30 hours
            108000 => 17,
            
            // 40 hours
            144000 => 18,

            // 60 hours
            216000 => 19,
            
            // 80 hours
            288000 => 20,
            
            // 100 hours
            360000 => 21,
        
            // 5 days
            432000 => 22,
            
            // 7 days
            604800 => 23,
        
            // 10 days
            864000 => 24,

            // 15 days
            1296000 => 25,
            
            // 20 days
            1728000 => 26,
            
            // 25 days
            2160000 => 27,

            // 30 days
            2592000 => 28,

            // 40 days
            3456000 => 29,

            // 50 days
            4320000 => 30,
        ];
    
        /** @var CompanionMarketItem $item */
        $section = $this->console->section();
        foreach ($items as $i => $item) {
            // set priority to a default 99
            $item->setPriority(99);
            
            // loop through priority times.
            if ($item->getAvgSaleDuration() > 1) {
                foreach ($priority as $unix => $value) {
                    // calculate avg sale duration
                    if ($item->getAvgSaleDuration() < $unix) {
                        $item->setPriority($value);
                        break;
                    }
                }
            }
            
            
            $section->overwrite("[{$i}] Item {$item->getItem()} = {$item->getPriority()}");
            $this->em->merge($item);
            
            if ($i % 100 == 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
    
        $this->em->flush();
    }
    
    /**
     * Get the market items
     */
    private function getMarketItems()
    {
        // check if a cache exists
        if (file_exists(self::CACHE_MARKET_ITEM_IDS)) {
            $modified     = filemtime(self::CACHE_MARKET_ITEM_IDS);
            $modifiedDate = date('Y-m-d H:i:s');
    
            $this->console->writeln("Market item cache found, generated: {$modifiedDate}");
            
            // if the modified date is under 1 day, keep it
            if ($modified > time() - (60*60*24)) {
                return json_decode(file_get_contents(self::CACHE_MARKET_ITEM_IDS));
            }
    
            $this->console->writeln('Market item cache time is older than 24 hours, creating a new one.');
        }
        
        // build a new market item cache
        $arr   = [];
        $ids   = Redis::Cache()->get('ids_Item');
        $total = count($ids);
        
        $section = $this->console->section();
        
        foreach ($ids as $i => $id) {
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            $section->overwrite("{$i} / {$total} :: {$id} :: {$item->Name_en}");
            
            if (isset($item->ItemSearchCategory->ID)) {
                $arr[] = $id;
            }
        }
        
        $section->overwrite('Caching marketable items');
        file_put_contents(self::CACHE_MARKET_ITEM_IDS, json_encode($arr));
        
        return $arr;
    }
}
