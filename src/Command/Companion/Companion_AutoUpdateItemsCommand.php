<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\Updater\MarketUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoUpdateItemsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoUpdateItemsCommand',
        'desc' => 'Auto-Update prices and history of all items on all servers.',
        'args' => [
            [ 'queue',      InputArgument::OPTIONAL, 'Item priority queue to process' ],
        ]
    ];

    /** @var MarketUpdater */
    private $marketUpdater;

    public function __construct(
        MarketUpdater $marketUpdater,
        $name = null
    ) {
        $this->marketUpdater = $marketUpdater;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         *   php bin/console Companion_AutoUpdateItemsCommand 100
         *   php bin/console Companion_AutoUpdateItemsCommand 101
         *
         *
         * Patreon Queue 1
         *   php bin/console Companion_AutoUpdateItemsCommand 10
         */
        $this->marketUpdater->update(
            $input->getArgument('queue')
        );
    }
}
