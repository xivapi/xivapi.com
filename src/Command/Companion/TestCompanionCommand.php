<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\Models\MarketHistory;
use App\Service\Companion\Models\MarketItem;
use App\Service\Companion\Models\MarketListing;
use App\Service\Content\GameServers;
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
    
    /**
     * Insert random items!
     */
    private function insertRandomData()
    {
        $total = 5;
        $intervals = 5;
    
        // Insert random data
        foreach (GameServers::LIST as $serverId => $server) {
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
                
                $documents[$item->ID] = $item;
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
    
    /**
     * Build a random item with random prices and random history
     */
    private function getRandomItemAndPrices($server, $id)
    {
        $item = new MarketItem();
        $item->ID       = "{$server}_{$id}";
        $item->Server   = $server;
        $item->ItemID   = $id;
        $item->Updated  = time();
        
        // add between 1 and 100 prices
        foreach(range(1, mt_rand(1,200)) as $num) {
            $item->Prices[] = (new MarketListing())->randomize();
        }
    
        // add between 1 and 100 prices
        foreach(range(1, mt_rand(500,3000)) as $num) {
            $item->History[] = (new MarketHistory())->randomize();
        }
    
        return $item;
    }
}
