<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\Updater\MarketQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoQueueItemsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoQueueItemsCommand',
        'desc' => 'Auto queue items to update',
    ];

    /** @var MarketQueue */
    private $marketQueue;

    public function __construct(
        MarketQueue $marketQueue,
        $name = null
    ) {
        $this->marketQueue = $marketQueue;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         *   php bin/console Companion_AutoQueueItemsCommand
         */
        $this->marketQueue->queue();
    }
}
