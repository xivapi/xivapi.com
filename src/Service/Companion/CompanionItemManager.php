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
    /** @var ConsoleOutput */
    private $output;
    /** @var array */
    private $positions = [];
    /** @var array */
    private $shops = [];
    /** @var array */
    private $itemToShops = [];
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
        $this->console->writeln("<info>-- Moving item priorities for Twintania and Spriggan --</info>");
        $this->console->writeln("<info>-- Start: {$date} --</info>");
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
        $this->console->writeln("- Complete");
        $this->console->writeln("- Duration: <comment>{$duration}</comment>");
    }

    /**
     * Populate the market database with marketable items so they can be auto-updated,
     * all newly added items start on priority 10 and will shift over time.
     */
    public function populateMarketDatabaseWithItems()
    {
        $start = Carbon::now();
        $date  = date('Y-m-d H:i:s');
        $this->console->writeln("<info>-- Market Item Populator --</info>");
        $this->console->writeln("<info>-- Start: {$date} --</info>");
        
        // map positions
        $this->console->writeln("Building NPC Map Positions");
        $this->buildMapPositions();
        
        // get all NPCs with a "GilShop" and obtain the items
        $this->console->writeln("Building NPCs with shops");
        $this->buildNPCWithShops();

        // get all items and handle their states
        $this->console->writeln("Building Items");
        $this->buildItemList();
        $totalItems        = number_format(count($this->items));
        $totalItemsInShops = number_format(count($this->itemToShops));
        $this->console->writeln("Total Items: <info>{$totalItems}</info>");
        $this->console->writeln("Total Items in Shops: <info>{$totalItemsInShops}</info>");
        
        // insert market item entries for online servers
        $this->console->writeln("Inserting market items");
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
        $start = Carbon::now();
        $date  = date('Y-m-d H:i:s');
        $this->console->writeln("<info>-- Calculate Item Update Priority --</info>");
        $this->console->writeln("<info>-- Start: {$date} --</info>");
    
        // get all items and handle their states
        $this->console->writeln("Building Items");
        $this->buildItemList();
        $totalItems = number_format(count($this->items));
        $this->console->writeln("Total Items: <info>{$totalItems}</info>");
        
        // update priorities
        $this->console->writeln("Updating Item Queues");
        $this->insertMarketItemQueues();
    
        // finished
        $duration = $start->diff(Carbon::now())->format('%h hr, %i min and %s sec');
        $this->console->writeln("- Complete");
        $this->console->writeln("- Duration: <comment>{$duration}</comment>");
    }
    
    /**
     * Build the map positions
     */
    private function buildMapPositions()
    {
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
    }
    
    /**
     * Build NPCs with shops
     */
    private function buildNPCWithShops()
    {
        $ids   = Redis::Cache()->get('ids_ENpcResident');
        $total = count($ids);
        
        $section = $this->console->section();
        foreach ($ids as $i => $id) {
            $i = $i+1;
            $section->overwrite("{$i}/{$total} - {$id}");
            
            // grab npc
            $npc = Redis::Cache()->get("xiv_ENpcResident_{$id}");
            if ($npc === null) {
                continue;
            }
    
            $section->overwrite("{$i}/{$total} - {$id} = {$npc->Name_en}");
            
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
                    'Map' => $this->positions[$npc->ID],
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
    
        $section->overwrite('- Complete');
    }
    
    /**
     * Builds an item list to insert
     */
    private function buildItemList()
    {
        $ids     = Redis::Cache()->get('ids_Item');
        $total   = count($ids);
        $section = $this->console->section();
 
        foreach ($ids as $i => $id) {
            $i = $i+1;
            $section->overwrite("{$i}/{$total} - {$id}");
            
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            if ($item === null) {
                continue;
            }
    
            $section->overwrite("{$i}/{$total} - {$id} = {$item->Name_en}");
            
            // ignore non-sellable
            if (!isset($item->ItemSearchCategory->ID)) {
                continue;
            }
            
            $this->items[] = $item->ID;
        }
    
        $section->overwrite('- Complete');
    }
    
    /**
     * Insert all the items
     */
    private function insertMarketItems()
    {
        $conn    = $this->em->getConnection();
        $total   = number_format(count($this->items));
        $section = $this->console->section();

        foreach ($this->items as $i => $itemId) {
            $i = $i+1;
            $section->overwrite("{$i}/{$total} - {$itemId}");
            
            foreach (GameServers::LIST as $serverId => $serverName) {
                // skip offline servers
                if (in_array($serverId, GameServers::MARKET_OFFLINE)) {
                    continue;
                }
    
                $section->overwrite("{$i}/{$total} - {$itemId} - {$serverName}");
    
                /**
                 * Skip existing ones
                 */
                $stmt = $conn->prepare("SELECT id FROM companion_market_items WHERE item = {$itemId} AND server = {$serverId} LIMIT 0,1");
                $stmt->execute();
    
                // exists
                if ($stmt->fetch()) {
                    continue;
                }
    
                $id    = Uuid::uuid4()->toString();
                $state = CompanionItem::STATE_UPDATING;
                
                // check if it has a shop
                $shops = $this->itemToShops[$itemId] ?? null;
                $shops = $shops ? array_unique($shops) : null;
                $shops = $shops ? json_encode($shops) : '';
                
                // if the item can be bought from the store, update state
                if ($shops) {
                    // insert source info
                    $stmt = $conn->prepare(
                        "REPLACE INTO companion_market_item_source (id, item, `data`) " .
                        "VALUES ('{$id}', {$itemId}, '{$shops}')"
                    );
                    $stmt->execute();
                    continue;
                }
    
                // get region
                $dc     = GameServers::getDataCenter($serverName);
                $region = GameServers::LIST_DC_REGIONS[$dc];
                $queue  = CompanionConfiguration::QUEUE_NEW_ITEM;
                
                $priority = time() - mt_rand(10,999999);
                
                // insert item entry
                $stmt = $conn->prepare(
                    "REPLACE INTO companion_market_items (id, updated, item, server, region, normal_queue, patreon_queue, state, priority) " .
                    "VALUES ('{$id}', 0, {$itemId}, {$serverId}, {$region}, {$queue}, 0, {$state}, {$priority});"
                );
                $stmt->execute();
            }
        }
    
        $section->overwrite('- Complete');
    }
    
    /**
     * Insert all the item queues, this goes through the history
     * of every item and finds out how often it sells and places
     * it in the correct queue.
     */
    private function insertMarketItemQueues()
    {
        $conn    = $this->em->getConnection();
        $total   = number_format(count($this->items));
        $section = $this->console->section();
    
        foreach ($this->items as $i => $itemId) {
            $i = $i + 1;
            $section->overwrite("{$i}/{$total} - {$itemId}");
    
            foreach (GameServers::LIST as $serverId => $serverName) {
                // skip offline servers
                if (in_array($serverId, GameServers::MARKET_OFFLINE)) {
                    continue;
                }
                
                // we can't do Spriggan and Twintania atm as very little history
                if (in_array($serverId, [ 66, 67 ])) {
                    continue;
                }

                /**
                 * Grab entry
                 */
                $stmt = $conn->prepare(
                    "SELECT * FROM companion_market_items
                     WHERE item = {$itemId} AND server = {$serverId} LIMIT 0,1"
                );
                $stmt->execute();
                $item = $stmt->fetch();
                
                // grab recorded document
                /** @var MarketItem $document */
                $document = $this->companionMarket->get($serverId, $itemId);
                
                // if no history, or never sells, update state and continue
                if (empty($document->History) || count($document->History) < CompanionConfiguration::MINIMUM_SALES_TO_UPDATE) {
                    $stmt = $conn->prepare(
                        sprintf(
                            "UPDATE companion_market_items
                             SET state = %s, normal_queue = %s
                             WHERE id = '%s'",
                            CompanionItem::STATE_NEVER_SOLD,
                            CompanionConfiguration::STATE_NEVER_SOLD,
                            $item['id']
                        )
                    );
                    $stmt->execute();
                    continue;
                }
                
                // work out the history
                $lastDate        = $document->History[0]->PurchaseDate;
                $historyCount    = 0;
                $historyCountMax = 100;
                $average         = [];
                
                // 1st one manually assigned
                unset($document->History[0]);
    
                // work out average history
                foreach ($document->History as $history) {
                    $diff = $lastDate - $history->PurchaseDate;
                    $lastDate = $history->PurchaseDate;
                    $average[] = $diff;
    
                    // stop after hitting max, we don't care about out of date sales.
                    $historyCount++;
                    if ($historyCount > $historyCountMax) {
                        break;
                    }
                }
    
                $saleAverage = floor(array_sum($average) / count($average));
    
                // set default queue
                $queue = CompanionConfiguration::QUEUE_DEFAULT;
                
                // find where it fits in our table
                foreach (CompanionConfiguration::PRIORITY_TIMES as $time => $queueNumber) {
                    // continue if the avg is above the time, skip
                    if ($saleAverage < $time) {
                        $queue = $queueNumber;
                        break;
                    }
                }
    
                // update with queue
                $stmt = $conn->prepare(
                    sprintf(
                        "UPDATE companion_market_items
                         SET state = %s, normal_queue = %s
                         WHERE id = '%s'",
                        CompanionItem::STATE_UPDATING,
                        $queue,
                        $item['id']
                    )
                );
                $stmt->execute();
            }
        }
    
        $section->overwrite('- Complete');
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
