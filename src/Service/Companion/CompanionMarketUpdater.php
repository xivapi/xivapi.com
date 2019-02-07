<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItem;
use App\Exception\CompanionMarketItemException;
use App\Repository\CompanionMarketItemRepository;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionMarketUpdater
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarketItemRepository */
    private $repository;
    /** @var Companion */
    private $companion;
    /** @var CompanionMarket */
    private $companionMarket;
    
    public function __construct(EntityManagerInterface $em, Companion $companion, CompanionMarket $companionMarket)
    {
        $this->em               = $em;
        $this->companion        = $companion;
        $this->companionMarket  = $companionMarket;
        $this->repository       = $this->em->getRepository(CompanionMarketItem::class);
        $this->console          = new ConsoleOutput();
    }
    
    public function process(string $dataCenter, int $priority = null, int $itemId = null)
    {
        $items = false;
    
        // grab servers to parse
        $servers = GameServers::LIST_DC[$dataCenter] ?? false;
        if ($servers === false) {
            $this->console->writeln('Invalid data center provided');
            return;
        }

        // if doing a single item (eg manual update)
        if ($itemId) {
            $items = $this->repository->findOneBy([ 'item' => $itemId ]);
        }
        
        // if doing a priority list of items
        if ($priority) {
            $items = $this->repository->findBy([ 'priority' => $priority ]);
        }
        
        if (empty($items)) {
            $this->console->writeln('No items');
            return;
        }
    
        $total = count($items);
        $this->console->writeln("Total Items: {$total}");
        $this->console->writeln('Getting item prices and history');
        
        // loop through items
        /** @var CompanionMarketItem $item */
        foreach ($items as $item) {
            $section = $this->console->section();

            // loop through servers
            foreach ($servers as $server) {
                // convert server to an int
                $serverName = $server;
                $server = GameServers::getServerId($server);

                $section->overwrite("> {$item->getItem()} {$serverName}");

                // try get existing item
                $marketItem = $this->companionMarket->get($server, $item->getItem());
                
                // if no market item, create one!
                if ($marketItem === null) {
                    $section->overwrite("> {$item->getItem()} {$serverName} - new item created");
                    $marketItem = new MarketItem($server, $item->getItem());
                }
                
                // grab prices from companion
                $section->overwrite("> {$item->getItem()} {$serverName} - Fetching prices ...");
                $prices  = $this->companion->getItemPrices($serverName, $item->getItem());
    
                // grab history from companion
                $section->overwrite("> {$item->getItem()} {$serverName} - Fetching history ...");
                $history = $this->companion->getItemHistory($serverName, $item->getItem());
                
                //
                // convert prices
                //
                $section->overwrite("> {$item->getItem()} {$serverName} - Converting price data");
                
                // reset prices
                $marketItem->Prices = [];
    
                // append current prices
                foreach ($prices->entries as $row) {
                    $marketItem->Prices[] = MarketListing::build($row);
                }
                
                //
                // convert historic data
                //
                $section->overwrite("> {$item->getItem()} {$serverName} - Converting history data");
                if ($history->history) {
                    foreach ($history->history as $row) {
                        // build a custom ID based on a few factors (History can't change)
                        // we don't include character name as I'm unsure if it changes if you rename yourself
                        $id = sha1(vsprintf("%s_%s_%s_%s_%s", [
                            $item->getItem(),
                            $row->stack,
                            $row->hq,
                            $row->sellPrice,
                            $row->buyRealDate
                        ]));
                        
                        // if this entry is in our history, then just finish
                        $found = false;
                        foreach ($marketItem->History as $existing) {
                            if ($existing->ID == $id) {
                                $found = true;
                                break;
                            }
                        }
                        
                        // once we've found an existing entry we don't need to add anymore
                        if ($found) {
                            break;
                        }

                        // add history to front
                        array_unshift($marketItem->History, MarketHistory::build($id, $row));
                    }
                }
                
                // put
                $section->overwrite("> {$item->getItem()} {$serverName} - Saved");
                $this->companionMarket->set($marketItem);
            }
            
            $section->writeln("Item {$item->getItem()} completed");
        }
        
        $this->console->writeln('Ready!');
    }
}
