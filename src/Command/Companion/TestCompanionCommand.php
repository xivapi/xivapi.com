<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
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
        $this->companionMarket->rebuildIndex();

        // insert random data
        $this->io->text('Inserting random data');
        $this->insertRandomData();
        
        
        $this->endClock();
    }
    
    private function insertRandomData()
    {
        // grab servers
        $servers = file_get_contents('https://xivapi.com/servers?key=testing');
        $servers = json_decode($servers);
        
        $total = 20000;
        $intervals = 100;
    
        // Insert random data
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
                
                // uncomment to debug
                // $this->companionMarket->set($item);continue;
                
                $documents[$item->id] = $item;
                $count++;
                
                // insert at every 100 intervals
                if ($count === $intervals) {
                    $this->io->progressAdvance($intervals);
                    $this->companionMarket->setBulk($documents);
        
                    unset($documents);
                    $documents = [];
                    $count = 0;
                }
            }
        
            if ($count > 0) {
                $this->companionMarket->setBulk($documents);
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
        $item->prices   = [];
        $item->history  = [];
        
        // add between 1 and 100 prices
        foreach(range(1, mt_rand(1,200)) as $num) {
            $obj = new MarketListing();
            $obj->id                 = $num + mt_rand(1,9999999999);
            $obj->time               = time();
            $obj->is_crafted         = mt_rand(0,100) % 4 == 0;
            $obj->is_hq              = mt_rand(0,50) % 5 == 0;
            $obj->price_per_unit     = mt_rand(1,9999);
            $obj->quantity           = mt_rand(1,999);
            $obj->price_total        = $obj->price_per_unit * $obj->quantity;
            $obj->retainer_id        = mt_rand(1,99999);
            $obj->craft_signature_id = mt_rand(1,99999);
            $obj->town_id            = mt_rand(1,4);
            $obj->stain_id           = mt_rand(1,25000);
            $obj->materia            = [];
            
            // add a random number of materia
            if (mt_rand(0,50) % 3 == 0) {
                foreach(range(1, mt_rand(1, 5)) as $num2) {
                    $obj->materia[] = mt_rand(2500,5000);
                }
            }
    
            $item->prices[] = $obj;
        }
    
        // add between 1 and 100 prices
        foreach(range(1, mt_rand(20,300)) as $num) {
            $obj = new MarketListing();
            $obj->id                 = $num + mt_rand(1,9999999999);
            $obj->time               = time();
            $obj->is_hq              = mt_rand(0,50) % 5 == 0;
            $obj->price_per_unit     = mt_rand(1,9999);
            $obj->quantity           = mt_rand(1,999);
            $obj->price_total        = $obj->price_per_unit * $obj->quantity;
            $obj->purchase_date      = time();
            $obj->character_name     = mt_rand(1,99999);

            $item->history[] = $obj;
        }
    
        return $item;
    }
}
