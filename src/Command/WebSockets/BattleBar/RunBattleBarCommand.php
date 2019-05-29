<?php

namespace App\Command\WebSockets\BattleBar;

use App\WebSockets\BattleBar\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunBattleBarCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('RunBattleBarCommand')
            ->setDescription('Run the Battle Bar WebSocket Server')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new Runner();
        $runner->start();
    }
}
