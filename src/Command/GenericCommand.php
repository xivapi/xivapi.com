<?php

namespace App\Command;

use App\Command\CommandConfigureTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenericCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoLoginAccountsCommand',
        'desc' => 'Re-login to each character to obtain a companion token.',
        'args' => [
            [ 'action', InputArgument::OPTIONAL, '(Optional) Either a list of servers or an account.' ]
        ]
    ];
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__METHOD__);
    }
}
