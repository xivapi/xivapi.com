<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionMarketUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoUpdateCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoUpdateCommand',
        'desc' => 'Auto-Update prices and history of all items on all servers.',
        'args' => [
            [ 'priority', InputArgument::OPTIONAL, 'Item priority queue to process' ],
            [ 'queue',    InputArgument::OPTIONAL, 'Queue number, this should be incremental' ],
            [ 'manual',   InputArgument::OPTIONAL, 'Update manual items' ],
            [ 'dcs',  InputArgument::OPTIONAL, 'Update for a specific account' ]
        ]
    ];

    /** @var CompanionMarketUpdater */
    private $companionMarketUpdater;

    public function __construct(CompanionMarketUpdater $companionMarketUpdater, $name = null)
    {
        $this->companionMarketUpdater = $companionMarketUpdater;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console Companion_AutoUpdateCommand 10 1
         * php bin/console Companion_AutoUpdateCommand 10 1 true
         */
        $this->companionMarketUpdater->update(
            $input->getArgument('priority'),
            $input->getArgument('queue'),
            $input->getArgument('manual') === 'true',
            $input->getArgument('dcs')
        );
    }
}
