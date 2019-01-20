<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketHistoryListing;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketItemListing;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCompanionCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var CompanionMarket */
    private $companionMarket;
    
    public function __construct(CompanionMarket $companionMarket, ?string $name = null)
    {
        $this->companionMarket = $companionMarket;
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this->setName('TestCompanionCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->startClock();
    
        // rebuild the index
        $this->io->text('Rebuilding ElasticSearch index');
        #$this->companionMarket->rebuildIndex(CompanionMarket::CURRENT);
        $this->companionMarket->rebuildIndex(CompanionMarket::HISTORY);

        // insert random data
        $this->io->text('Inserting random data');
        #$this->insertRandomCurrentPrices();
        $this->insertRandomHistoricPrices();
        
        
        $this->endClock();
    }
    
    private function insertRandomHistoricPrices()
    {
        // grab servers
        $servers = file_get_contents('https://xivapi.com/servers?key=testing');
        $servers = json_decode($servers);
        
        $total = 15000;
    
        //
        // Insert random data
        //
        foreach ($servers as $serverId => $server) {
            $server = strtolower($server);
            $date   = date('Y-m-d H:i:s');
            $memory = round((memory_get_peak_usage(true)/1024/1024), 2);
        
            $this->io->text("<info>[{$memory} mb] [{$date}] Inserting {$total} entries to: {$server}</info>");
            $this->io->progressStart($total);
            $documents = [];
            $count = 0;
        
            foreach(range(1, $total) as $num) {
                $item = $this->getRandomItemHistory($serverId, $num);
                $documents[$item->id] = $item;
                $count++;
            
                // insert at every 100 intervals
                if ($count === 25) {
                    $this->io->progressAdvance(25);
                    $this->companionMarket->setHistoryBulk($documents);
                
                    unset($documents);
                    $documents = [];
                    $count = 0;
                }
            }
        
            if ($count > 0) {
                $this->companionMarket->setHistoryBulk($documents);
            }
        
            $this->io->progressFinish();
        }
    }
    
    private function insertRandomCurrentPrices()
    {
        // grab servers
        $servers = file_get_contents('https://xivapi.com/servers?key=testing');
        $servers = json_decode($servers);
    
        $total = 15000;
        
        //
        // Insert random data
        //
        foreach ($servers as $serverId => $server) {
            $server = strtolower($server);
            $date   = date('Y-m-d H:i:s');
            $memory = round((memory_get_peak_usage(true)/1024/1024), 2);
        
            $this->io->text("<info>[{$memory} mb] [{$date}] Inserting {$total} entries to: {$server}</info>");
            $this->io->progressStart($total);
            $documents = [];
            $count = 0;
        
            foreach(range(1, $total) as $num) {
                $item = $this->getRandomItemAndPrices($serverId, $num);
                $documents[$item->id] = $item;
                $count++;
            
                // insert at every 100 intervals
                if ($count === 100) {
                    $this->io->progressAdvance(100);
                    $this->companionMarket->setPricesBulk($documents);
                
                    unset($documents);
                    $documents = [];
                    $count = 0;
                }
            }
        
            if ($count > 0) {
                $this->companionMarket->setPricesBulk($documents);
            }
        
            $this->io->progressFinish();
        }
    }
    
    private function getRandomItemAndPrices($server, $id)
    {
        $item = new MarketItem();
        $item->id       = "{$server}_{$id}";
        $item->server   = $server;
        $item->item_id  = $id;
        $item->total    = mt_rand(1,100);
        $item->prices   = [];
        
        // add between 1 and 100 prices
        foreach(range(1, $item->total) as $num) {
            $listing = new MarketItemListing();
            $listing->id                 = $num + mt_rand(1,9999999999);
            $listing->time               = time();
            $listing->is_crafted         = mt_rand(0,100) % 4 == 0;
            $listing->is_hq              = mt_rand(0,50) % 5 == 0;
            $listing->price_per_unit     = mt_rand(1,9999);
            $listing->quantity           = mt_rand(1,999);
            $listing->price_total        = $listing->price_per_unit * $listing->quantity;
            $listing->retainer_id        = mt_rand(1,99999);
            $listing->craft_signature_id = mt_rand(1,99999);
            $listing->town_id            = mt_rand(1,4);
            $listing->stain_id           = mt_rand(1,25000);
            $listing->materia            = [];
            
            // add a random number of materia
            if (mt_rand(0,50) % 3 == 0) {
                foreach(range(1, mt_rand(1, 5)) as $num2) {
                    $listing->materia[] = mt_rand(2500,5000);
                }
            }
    
            $item->prices[] = $listing;
        }

        return $item;
    }
    
    private function getRandomItemHistory($server, $id)
    {
        $item = new MarketHistory();
        $item->id       = "{$server}_{$id}";
        $item->server   = $server;
        $item->item_id  = $id;
        $item->total    = mt_rand(1,1000);
        $item->history = [];
        
        // add between 1 and 100 prices
        foreach(range(1, $item->total) as $num) {
            $listing = new MarketHistoryListing();
            $listing->id                 = $num + mt_rand(1,9999999999);
            $listing->time               = time();
            $listing->is_hq              = mt_rand(0,50) % 5 == 0;
            $listing->price_per_unit     = mt_rand(1,9999);
            $listing->quantity           = mt_rand(1,999);
            $listing->price_total        = $listing->price_per_unit * $listing->quantity;
            $listing->character_name     = mt_rand(1,99999);
            $listing->purchase_date      = time();

            $item->history[] = $listing;
        }
        
        return $item;
    }
}
