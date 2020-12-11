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

class ResetCountersCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ResetCountersCommand')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // this cronjob is run at X:59 and deletes the stats for the following hour.

        $hour = date('G');
        $hour = ($hour + 1);
        $hour = $hour >= 24 ? 0 : $hour;

        $key  = "stat_requests_". $hour;

        Redis::cache()->delete($key);
    }
}
