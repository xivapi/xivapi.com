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
        'name' => 'GenericCommand',
        'desc' => 'Desc',
        'args' => [
            [ 'action', InputArgument::OPTIONAL, 'xxxxx' ]
        ]
    ];

    public function __construct($name = null)
    {
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__METHOD__);
    }
}
