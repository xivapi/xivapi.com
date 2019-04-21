<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionCensus
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionMarketItemEntryRepository */
    private $entries;
    /** @var CompanionMarket */
    private $market;
    /** @var CompanionItemManager */
    private $items;
    /** @var CompanionItemManager */
    private $itemsSellable;
    /** @var ConsoleOutput */
    private $console;
    /** @var array */
    private $results =[];

    public function __construct(EntityManagerInterface $em, CompanionMarket $market, CompanionItemManager $items)
    {
        $this->em      = $em;
        $this->market  = $market;
        $this->items   = $items;
        $this->entries = $em->getRepository(CompanionMarketItemEntry::class);
        $this->console = new ConsoleOutput();
    }
    
    public function run()
    {
        $start = date('Y-m-d H:i:s');
        $this->console->writeln('Building Census');
        $this->console->writeln("Start time: {$start}");
        
        // get sellable items
        $this->itemsSellable = $this->items->getMarketItemIds();
        
        // build census for each server
        foreach (GameServers::LIST as $server) {
            $this->console->writeln("Building census for: {$server}");
            
            $this->buildCensusForServer(
                GameServers::getServerId($server)
            );
        }
    }
    
    /**
     * @param int $server
     */
    private function buildCensusForServer(int $server)
    {
        $census = (Object)[
            'TotalListings'                 => 0,
            'TotalItemsWithNoListing'       => 0,
            'TotalItemPricePerUnit'         => 0,
            'TotalItemQuantity'             => 0,
            'TotalItemPriceTotal'           => 0,
            'TotalRetainers'                => 0,
            'TotalCrafters'                 => 0,
            'TotalItemsWithMateria'         => 0,
            'TotalItemsHQ'                  => 0,
            'TotalItemsNQ'                  => 0,
            'TotalItemsCrafted'             => 0,

            'TotalHistory'                  => 0,
            'TotalHistoryPricePerUnit'      => 0,
            'TotalHistoryQuantity'          => 0,
            'TotalHistoryPriceTotal'        => 0,
            'TotalHistoryHQ'                => 0,
            'TotalHistoryNQ'                => 0,
            'TotalBuyers'                   => 0,
            
            'TotalMateria'                  => 0,
            'MostCommonMateria'             => [],
            'MostcommonTown'                => [],

            'Retainers'                     => [],
            'Crafters'                      => [],
            'Buyers'                        => [],
            
            'PurchasePerAmPm'               => [],
            'PurchasePerHour'               => [],
        ];
        
        $section = $this->console->section();
        
        foreach ($this->itemsSellable as $item) {
            $market = $this->market->get($server, $item, null, true);
    
            $date = date('Y-m-d H:i:s');
            $section->overwrite("{$date} - Item: {$item}");
            
            /**
             * Remove all listing from the past 24 hours
             */
            $deadline = time() - (60 * 60 * 24);
            foreach ($market->History as $i => $his) {
                if ($his->PurchaseDate < $deadline) {
                    unset($market->History[$i]);
                }
            }
    
            /**
             * History
             */
            $census->TotalHistory = count($market->History);
            foreach ($market->History as $history) {
                $census->TotalHistoryPricePerUnit  += $history->PricePerUnit;
                $census->TotalHistoryQuantity      += $history->Quantity;
                $census->TotalHistoryPriceTotal    += $history->PriceTotal;
    
                if ($history->IsHQ) {
                    $census->TotalHistoryHQ += 1;
                } else {
                    $census->TotalHistoryNQ += 1;
                }
    
                $census->Buyers[$history->CharacterName] = 1;
                
                // purchases over time
                $ampm = date('a', $history->PurchaseDate);
                $hour = date('G', $history->PurchaseDate);
                $census->PurchasePerAmPm[$ampm] = isset($census->PurchasePerAmPm[$ampm]) ? $census->PurchasePerAmPm[$ampm] + 1 : 1;
                $census->PurchasePerHour[$hour] = isset($census->PurchasePerHour[$hour]) ? $census->PurchasePerHour[$hour] + 1 : 1;
            }
            
            $census->TotalBuyers += count($census->Buyers);
    
            /**
             * If no prices, skip!
             */
            if (empty($market->Prices)) {
                $census->TotalItemsWithNoListing += 1;
                continue;
            }
            
            /**
             * Prices
             */
            $census->TotalListings += count($market->Prices);
            foreach ($market->Prices as $price) {
                $census->TotalItemPricePerUnit  += $price->PricePerUnit;
                $census->TotalItemQuantity      += $price->Quantity;
                $census->TotalItemPriceTotal    += $price->PriceTotal;
                
                if ($price->IsHQ) {
                    $census->TotalItemsHQ += 1;
                } else {
                    $census->TotalItemsNQ += 1;
                }
                
                if ($price->IsCrafted) {
                    $census->TotalItemsCrafted += 1;
                }

                // track retainer + crafters
                $census->Retainers[$price->RetainerName] = 1;
                $census->Crafters[$price->CreatorSignatureName] = 1;
                
                if ($price->Materia) {
                    $census->TotalItemsWithMateria += 1;
                    $census->TotalMateria += count($price->Materia);
                    
                    foreach ($price->Materia as $id) {
                        $census->MostCommonMateria[$id] = isset($census->MostCommonMateria[$id]) ? $census->MostCommonMateria[$id] + 1 : 1;
                    }
                }
    
                $census->MostcommonTown[$price->TownID] = isset($census->MostcommonTown[$price->TownID]) ? $census->MostcommonTown[$price->TownID] + 1 : 1;
            }
    
            arsort($census->MostCommonMateria);
            arsort($census->MostcommonTown);
            
            $census->TotalRetainers += count($census->Retainers);
            $census->TotalCrafters += count($census->Buyers);
            
            unset($market);
        }
    
        // reduce materia to top 10.
        array_splice($MostCommonMateria, 10);
        
        // remove some held data we don't need
        unset(
            $census->Retainers,
            $census->Crafters,
            $census->Buyers
        );
        
        file_put_contents(
            __DIR__."/Census/Census_{$server}.json",
            json_encode($census, JSON_PRETTY_PRINT)
        );
        
        unset($census);
    }
}
