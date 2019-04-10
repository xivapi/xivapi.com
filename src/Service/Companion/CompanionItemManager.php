<?php

namespace App\Service\Companion;

use App\Command\Companion\Companion_AutoPrioritiseLoginsCommand;
use App\Entity\CompanionMarketItemEntry;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Service\Companion\Models\MarketItem;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
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
    /** @var CompanionMarketItemEntryRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em, CompanionMarket $companionMarket)
    {
        $this->em               = $em;
        $this->companionMarket  = $companionMarket;
        $this->output           = new ConsoleOutput();
        $this->repository       = $this->em->getRepository(CompanionMarketItemEntry::class);
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

        Redis::Cache()->set(self::MARKET_ITEMS_CACHE_KEY, $items);

        return $items;
    }

    /**
     * Populate the market database with marketable items so they can be auto-updated,
     * all newly added items start on priority 10 and will shift over time.
     */
    public function populateMarketDatabaseWithItems(string $server = null)
    {
        $total = count($this->getMarketItemIds());
        $this->output->writeln("Adding: {$total} items to the companion market database.");
        $section = $this->output->section();
        
        $server = $server ? GameServers::getServerId($server) : null;

        // loop through all marketable items.
        foreach ($this->getMarketItemIds() as $itemId) {
            $section->overwrite("Adding: {$itemId}");

            // loop through each server
            foreach (GameServers::LIST as $serverId => $serverName) {
                if ($server && $server != $serverId) {
                    continue;
                }
                
                // check for an existing entry
                $obj = $this->repository->findOneBy([
                    'item' => $itemId,
                    'server' => $serverId
                ]);

                // if it exists, skip
                if ($obj) {
                    continue;
                }

                // create new entry with a priority of 10
                $this->em->persist(
                    new CompanionMarketItemEntry(
                        $itemId,
                        $serverId,
                        CompanionConfiguration::PRIORITY_ITEM_IS_NEW
                    )
                );
            }

            // flush and clear
            $this->em->flush();
            $this->em->clear();
        }

        $totalItems = $total * count(GameServers::LIST);
        $this->output->writeln("Complete, total entries in database: {$totalItems}");
    }

    /**
     * Handles the item update priority
     */
    public function calculateItemUpdatePriority()
    {
        $this->output->writeln('Calculating item update priority ...');
        $this->output->writeln("Start Time: ". date('Y-m-d H:i:s'));

        $section = $this->output->section();

        $this->output->writeln("Getting market item ids ...");
        $items = $this->getMarketItemIds();

        // loop through all marketable items.
        foreach ($items as $itemId) {
            $section->overwrite("Calculating priority for item: {$itemId}");

            // loop through each server
            foreach (GameServers::LIST as $serverId => $serverName) {
                // grab recorded document
                /** @var MarketItem $document */
                $document = $this->companionMarket->get($serverId, $itemId);

                // grab market db entry
                /** @var CompanionMarketItemEntry $obj */
                $obj = $this->repository->findOneBy([
                    'item' => $itemId,
                    'server' => $serverId
                ]);
                
                // skip, may not exist (dead server)
                if ($obj === null) {
                    continue;
                }

                // ------------------------------------------------------------
                // Calculate
                // ------------------------------------------------------------

                // if the item is still "new", ignore for now (another command handles it)
                if ($obj->getPriority() === CompanionConfiguration::PRIORITY_ITEM_IS_NEW) {
                    $this->em->persist($obj);
                    continue;
                }

                // if no history, it has never been sold
                if (empty($document->History)) {
                    $obj->setPriority(CompanionConfiguration::PRIORITY_ITEM_LOW_SALES);
                    $this->em->persist($obj);
                    continue;
                }

                // record sale histories, we start with the time the item was last updated.
                $lastDate        = $obj->getUpdated();
                $historyCount    = 0;
                $historyCountMax = 250;
                $average         = [];

                foreach ($document->History as $history) {
                    $diff     = $lastDate - $history->PurchaseDate;
                    $lastDate = $history->PurchaseDate;

                    // append on sale time difference
                    if ($diff > CompanionConfiguration::ITEM_HISTORY_THRESHOLD) {
                        $average[] = $diff;
                    }

                    // stop after hitting max, we don't care about out of date sales.
                    $historyCount++;
                    if ($historyCount > $historyCountMax) {
                        break;
                    }
                }

                // item has had less than 5 sales, too low to make a call against
                if (count($average) < CompanionConfiguration::ITEM_HISTORY_AVG_REQUIREMENT) {
                    $obj->setPriority(CompanionConfiguration::PRIORITY_ITEM_LOW_SALES);
                    $this->em->persist($obj);
                    continue;
                }

                $saleAverage = floor(array_sum($average) / count($average));
                
                // set default
                $obj->setPriority(CompanionConfiguration::PRIORITY_TIMES_DEFAULT);

                // find where it fits in our table
                foreach (CompanionConfiguration::PRIORITY_TIMES as $time => $priority) {
                    // continue if the avg is above the time
                    if ($saleAverage > $time) {
                        continue;
                    }

                    // sale avg is below the priority time, set the value
                    $obj->setPriority($priority);
                    break;
                }

                $this->em->persist($obj);
            }

            // flush and clear
            $this->em->flush();
            $this->em->clear();
        }

        $this->output->writeln('Finished calculating item priority.');
        $this->output->writeln("End Time: ". date('Y-m-d H:i:s'));
    }
}
