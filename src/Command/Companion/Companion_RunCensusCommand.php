<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionCensus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_RunCensusCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_RunCensusCommand',
        'desc' => 'Run market census',
    ];

    /** @var CompanionCensus */
    private $census;

    public function __construct(CompanionCensus $census, $name = null)
    {
        $this->census = $census;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console Companion_RunCensusCommand
         */
        $this->census->run();
    }
}
