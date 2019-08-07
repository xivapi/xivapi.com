<?php

namespace App\Command\Misc;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionMarketDoc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCompanionDataCommand extends Command
{
    /** @var CompanionMarket */
    private $cm;
    /** @var CompanionMarketDoc */
    private $cmd;
    
    public function __construct(CompanionMarket $cm, CompanionMarketDoc $cmd, ?string $name = null)
    {
        $this->cm = $cm;
        $this->cmd = $cmd;
        
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('MigrateCompanionDataCommand')
            ->setDescription('Downloads a bunch of info from Lodestone, including icons.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new ConsoleOutput();
        $console = $console->section();

        $ids     = Redis::Cache()->get("ids_Item");
        $total   = count((array)$ids);
        $count   = 0;

        foreach ($ids as $itemId) {
            $count++;
            $console->overwrite("Convert item: {$itemId} - {$count}/{$total}");

            foreach (GameServers::LIST as $serverId => $serverName) {
                $doc = $this->cm->get($serverId, $itemId, 9999, 9999, true);
                $this->cmd->save($serverId, $itemId, $doc);
            }
        }

        $console->overwrite("Done!");
    }
}
