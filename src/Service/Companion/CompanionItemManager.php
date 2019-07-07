<?php

namespace App\Service\Companion;

use App\Common\Constants\RedisConstants;
use App\Common\Game\GameServers;
use App\Entity\CompanionItem;
use App\Entity\MapPosition;
use App\Service\Companion\Models\MarketItem;
use App\Common\Service\Redis\Redis;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionItemManager
{
    const MARKET_ITEMS_CACHE_KEY = 'companion_market_items';

    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionMarket */
    private $companionMarket;
    /** @var array */
    private $positions = [];
    /** @var array */
    private $itemToShops = [];
    /** @var array */
    private $itemToInfo = [];
    
    /** @var array */
    private $items = [];

    public function __construct(EntityManagerInterface $em, CompanionMarket $companionMarket)
    {
        $this->em               = $em;
        $this->companionMarket  = $companionMarket;
        $this->console          = new ConsoleOutput();
    }

    public function moveNewServerItemIds()
    {
        $start = Carbon::now();
        $date  = date('Y-m-d H:i:s');
        $this->console->writeln("<info>Moving item priorities for Twintania and Spriggan</info>");
        $this->console->writeln("<info>Start: {$date}</info>");
        $section = $this->console->section();

        $sql = 'SELECT item, normal_queue FROM companion_market_items WHERE server = 46';
        $sql = $this->em->getConnection()->prepare($sql);
        $sql->execute();

        foreach ($sql->fetchAll() as $row) {
            $itemId = $row['item'];
            $queue  = $row['normal_queue'];

            // update spriggan and wintania
            try {
                $sql = "UPDATE companion_market_items SET normal_queue = {$queue} WHERE item = {$itemId} AND normal_queue = 70";
                $sql = $this->em->getConnection()->prepare($sql);
                $sql->execute();
            } catch (\Exception $e) {

            }

            $section->overwrite('- Updated: '. $itemId . ' to '. $queue);
        }

        // finished
        $duration = $start->diff(Carbon::now())->format('%h hr, %i min and %s sec');
        $this->console->writeln("Duration: <comment>{$duration}</comment>");
    }

    /**
     * Populate the market database with marketable items so they can be auto-updated,
     * all newly added items start on priority 10 and will shift over time.
     */
    public function populateMarketDatabaseWithItems()
    {
        $start = Carbon::now();
        $date  = date('Y-m-d H:i:s');
        $this->console->writeln("<info>Market Item Populator</info>");
        $this->console->writeln("<info>Start: {$date}</info>");
        
        // map positions
        $this->buildMapPositions();
        
        // get all NPCs with a "GilShop" and obtain the items
        $this->buildNPCWithShops();

        // get all items and handle their states
        $this->buildItemList();
        
        // insert market item entries for online servers
        $this->insertMarketItems();
    
        // finished
        $duration = $start->diff(Carbon::now())->format('%h hr, %i min and %s sec');
        $this->console->writeln("- Complete");
        $this->console->writeln("- Duration: <comment>{$duration}</comment>");
    }
    
    /**
     * Handles the item update priority
     */
    public function calculateItemUpdatePriority()
    {
        /*
        $conn  = $this->em->getConnection();
        $items = file_get_contents(__DIR__.'/companion_market_items.csv');
        $items = explode("\n", $items);
        $items = array_unique($items);
        
        foreach ($items as $itemId) {
            $sql = "UPDATE companion_market_items SET state = ?, normal_queue = ? WHERE item = ?";
            $sql = $conn->prepare($sql);
            $sql->execute([ CompanionItem::STATE_UPDATING, 70, $itemId ]);
        }
        return;
        */
        
        
        
        $start = Carbon::now();
        $date  = date('Y-m-d H:i:s');
        $this->console->writeln("<info>Calculate Item Update Priority</info>");
        $this->console->writeln("<info>Start: {$date}</info>");
    
        // get all items and handle their states
        $this->buildItemList();
        
        // update priorities
        $this->insertMarketItemQueues();
    
        // finished
        $duration = $start->diff(Carbon::now())->format('%h hr, %i min and %s sec');
        $this->console->writeln("- Duration: <comment>{$duration}</comment>");
    }
    
    /**
     * Build the map positions
     */
    private function buildMapPositions()
    {
        $this->console->writeln("Building map positions ...");
        
        $mapPositions = $this->em->getRepository(MapPosition::class)->findBy([
            'Type' => 'NPC',
        ]);
    
        /** @var MapPosition $mp */
        foreach ($mapPositions as $mp) {
            $map = Redis::Cache()->get("xiv_Map_{$mp->getMapID()}");
            
            $this->positions[$mp->getENpcResidentID()] = [
                'Map' => $map,
                'Position' => [
                    'X' => $mp->getPosX(),
                    'Y' => $mp->getPosY(),
                ],
                'Pixels' => [
                    'X' => $mp->getPixelX(),
                    'Y' => $mp->getPixelY(),
                ],
            ];
        }
    
        unset($mapPositions);
    
        $this->console->writeln("Complete");
    }
    
    /**
     * Build NPCs with shops
     */
    private function buildNPCWithShops()
    {
        $this->console->writeln("Building NPC stores ...");
        
        $ids = Redis::Cache()->get('ids_ENpcResident');
        foreach ($ids as $i => $id) {
            // grab npc
            $npc = Redis::Cache()->get("xiv_ENpcResident_{$id}");
            if ($npc === null) {
                continue;
            }
    
            // if no gil shop, skip
            if (!isset($npc->GilShop) || empty($npc->GilShop)) {
                continue;
            }
            
            foreach ($npc->GilShop as $gs) {
                /**
                 * Save gil shop data
                 */
                $gilShopData = [
                    'NPC_ID' => $npc->ID,
                    'NPC_Name_en' => $npc->Name_en,
                    'NPC_Name_de' => $npc->Name_de,
                    'NPC_Name_fr' => $npc->Name_fr,
                    'NPC_Name_ja' => $npc->Name_ja,
                    'Shop_ID' => $gs->ID,
                    'Shop_Name_en' => $gs->Name_en,
                    'Shop_Name_de' => $gs->Name_de,
                    'Shop_Name_fr' => $gs->Name_fr,
                    'Shop_Name_ja' => $gs->Name_ja,
                    'Map' => $this->positions[$npc->ID] ?? null,
                ];
                
                Redis::Cache()->set("xiv_GilShopData_{$gs->ID}", $gilShopData, RedisConstants::TIME_10_YEAR);
                
                // record all shops an item is in.
                foreach ($gs->Items as $item) {
                    if (!isset($this->itemToShops[$item->ID])) {
                        $this->itemToShops[$item->ID] = [];
                    }
                    
                    if (in_array($item->ID, $this->itemToShops[$item->ID])) {
                        continue;
                    }
    
                    $this->itemToShops[$item->ID][] = $gs->ID;
                }
            }
            
            unset($npc);
        }
    }
    
    /**
     * Builds an item list to insert
     */
    private function buildItemList()
    {
        $this->console->writeln("Building item list and item information ...");

        $ids = (array)Redis::Cache()->get('ids_Item');
        $ids = array_unique($ids);
        
        foreach ($ids as $i => $id) {
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            if ($item === null) {
                continue;
            }
    
            // ignore non-sellable
            if (!isset($item->ItemSearchCategory->ID)) {
                continue;
            }
            
            $this->items[] = $item->ID;
            
            // add some info
            $this->itemToInfo[$item->ID] = [
                'ID'         => $item->ID,
                'CatId'      => $item->ClassJobCategory->ID ?? null,
                'KindId'     => $item->ItemKind->ID ?? null,
                'UIName'     => $item->ItemUICategory->Name_en ?? null,
                'LevelEquip' => $item->LevelEquip,
                'LevelItem'  => $item->LevelItem,
                'Rarity'     => $item->Rarity,
            ];
        }
    }
    
    /**
     * Insert all the items
     */
    private function insertMarketItems()
    {
        $servers = GameServers::LIST;
        
        // remove offline servers
        foreach ($servers as $serverId => $serverName) {
            if (in_array($serverId, GameServers::MARKET_OFFLINE)) {
                unset($servers[$serverId]);
            }
        }
  
        $conn    = $this->em->getConnection();
        $count   = 0;
        $total   = count($this->items) * count($servers);
        $uniq    = [];
        $uniqShop= [];

        $this->console->writeln("Inserting {$total} items into the market table");
        $section = $this->console->section();
    
        $insert  = [];
        $columns = [
            'updated',
            'item',
            'server',
            'normal_queue',
            'manual_queue',
            'patreon_queue',
            'state',
            'priority'
        ];

        foreach ($this->items as $i => $itemId) {
            // grab item info
            $itemInfo = $this->itemToInfo[$itemId];
    
            //
            // ADD: Where to buy from vendor
            //
            $hasShop = false;
            $shops = $this->itemToShops[$itemId] ?? null;
            if ($shops && !isset($uniqShop[$itemId])) {
                $hasShop = true;
                $uniqShop[$itemId] = 1;
                $shops = json_encode(array_unique($shops));
                $stmt = $conn->prepare(
                    "INSERT IGNORE INTO companion_market_item_source (`item`,`data`) VALUES ('{$itemId}','{$shops}')"
                );
                $stmt->execute();
            }
            
            // loop through servers to add each item
            foreach ($servers as $serverId => $serverName) {
                // setup
                $queue    = CompanionConfiguration::QUEUE_NEW_ITEM;
                $state    = CompanionItem::STATE_UPDATING;
                $priority = time() - mt_rand(10,999999);
                $uniqId   = $serverId . $itemId;
                
                // skip any unique's added, unsure why this happens..
                // todo - investigate
                if (isset($uniq[$uniqId])) {
                    continue;
                }
    
                $uniq[$uniqId] = 1;
                
                //
                // If the item is gear and is below level 60, it wont be updated
                //
                if (in_array($itemInfo['KindId'], [1,2,3,4]) && $itemInfo['LevelEquip'] < 68) {
                    // if the item is armor, equip level 1 and item level 1, it can be updated
                    if (in_array($itemInfo['KindId'], [3]) && $itemInfo['LevelEquip'] == 1 && $itemInfo['LevelItem'] == 1) {
                    // if item is rarity 3 (blue) it can be updated
                    } elseif ($itemInfo['Rarity'] == 3) {
                    // else, do not update
                    } else {
                        $state = CompanionItem::STATE_LOW_LEVEL;
                        $queue = CompanionConfiguration::QUEUE_NOT_UPDATING;
                    }
                }
                
                //
                // If the item can be bought from a shop and is either:
                // a Medicine, material, or Other. Then don't update
                //
                if (in_array($itemInfo['KindId'], [5,6,7]) && $hasShop) {
                    $state = CompanionItem::STATE_BOUGHT_FROM_NPC;
                    $queue = CompanionConfiguration::QUEUE_NOT_UPDATING;
                }
                
                // prep-insert
                $insert[] = [
                    0,
                    $itemId,
                    $serverId,
                    $queue,
                    0,
                    0,
                    $state,
                    $priority
                ];
            }

            // insert at count intervals
            if (count($insert) > 200 || $count > ($total - 300)) {
                //
                // Insert item stuff
                //
                $count += count($insert);
                $section->overwrite(number_format($count) . "/". number_format($total));
                
                // build query
                $sql = "INSERT IGNORE INTO companion_market_items (%s) VALUES %s";
                $sqlInsert = [];
                
                foreach ($insert as $in) {
                    $sqlInsert[] = "(". implode(',', $in) .")";
                }
                
                $sql = sprintf(
                    $sql,
                    implode(',', $columns),
                    implode(',', $sqlInsert)
                );
                
                // insert and reset
                $insert = [];
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
        }
    }
    
    /**
     * Insert all the item queues, this goes through the history
     * of every item and finds out how often it sells and places
     * it in the correct queue.
     */
    private function insertMarketItemQueues()
    {
        $this->console->writeln("Updating server market priority");
        
        $conn    = $this->em->getConnection();
        $section = $this->console->section();
        $total   = count($this->items);
        $count   = 0;

        // loop through prices
        foreach ($this->items as $i => $itemId) {
            $count++;
    
            // grab server stuff
            $serverId = GameServers::getServerId(GameServers::MARKET_SERVER);
    
            // set default queue
            $queue = CompanionConfiguration::QUEUE_DEFAULT;
    
            //
            // Grab current entry
            //
            $stmt = $conn->prepare(
                "SELECT * FROM companion_market_items
                     WHERE item = {$itemId} AND server = {$serverId} LIMIT 1"
            );
            $stmt->execute();
            $item = $stmt->fetch();
    
            //
            // SKIP: Item is new
            //
            if ($item['normal_queue'] === 70) {
                continue;
            }
            
            //
            // SKIP: Item not being updated
            //
            if ($item['state'] != CompanionItem::STATE_UPDATING) {
                continue;
            }
    
            //
            // grab recorded document
            //
            /** @var MarketItem $document */
            $document = $this->companionMarket->get($serverId, $itemId);
    
            // if no history or never sells, update state and continue
            if (empty($document->History) || count($document->History) < CompanionConfiguration::MINIMUM_SALES_TO_UPDATE) {
                $this->insertMarketItemQueuesSetNewQueue($itemId, CompanionItem::STATE_UPDATING, CompanionConfiguration::QUEUE_NEVER_SOLD);
                continue;
            }
    
            // work out the history
            $lastDate   = $document->History[0]->PurchaseDate;
            $maxHistory = 50;
            $average    = [];
    
            // 1st one manually assigned
            unset($document->History[0]);
    
            // work out average history
            foreach ($document->History as $history) {
                $diff      = $lastDate - $history->PurchaseDate;
                $lastDate  = $history->PurchaseDate;
                $average[] = $diff;
        
                // stop after hitting max, we don't care about out of date sales.
                if (count($average) > $maxHistory) {
                    break;
                }
            }
    
            $saleAverage = floor(array_sum($average) / count($average));
    
            // find where it fits in our table
            foreach (CompanionConfiguration::PRIORITY_TIMES as $time => $queueNumber) {
                // continue if the avg is above the time, skip
                if ($saleAverage < $time) {
                    $queue = $queueNumber;
                    break;
                }
            }
    
            //
            // Update queue for item
            //
            $section->overwrite("{$count}/{$total} - Item: {$itemId}, Queue: {$queue}");
            $this->insertMarketItemQueuesSetNewQueue($itemId, CompanionItem::STATE_UPDATING, $queue);
        }
    }

    /**
     * Update queue
     */
    private function insertMarketItemQueuesSetNewQueue($itemId, $state, $queue)
    {
        $conn = $this->em->getConnection();
        $sql = "UPDATE companion_market_items SET state = ?, normal_queue = ? WHERE item = ?";
        $sql = $conn->prepare($sql);
        $sql->execute([ $state, $queue, $itemId ]);
    }
    
    /**
     * Get a list of market item ids
     */
    public function getMarketItemIds(): array
    {
        // if cached, return that
        if ($items = Redis::Cache()->get(self::MARKET_ITEMS_CACHE_KEY)) {
            return $items;
        }

        // build new cache
        $items = [];
        foreach (Redis::Cache()->get('ids_Item') as $itemId) {
            $item = Redis::Cache()->get("xiv_Item_{$itemId}");
            if (isset($item->ItemSearchCategory->ID)) {
                $items[] = $itemId;
            }
        }

        Redis::Cache()->set(self::MARKET_ITEMS_CACHE_KEY, $items, RedisConstants::TIME_10_YEAR);
        return $items;
    }
    
}
