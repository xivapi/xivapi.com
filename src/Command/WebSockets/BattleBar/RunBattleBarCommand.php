<?php

namespace App\Command\WebSockets;

use App\WebSockets\BattleBar\BattleBarRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunBattleBarCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('RunBattleBarCommand')
            ->setDescription('')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new BattleBarRunner();
        $runner->start();
    }
}
