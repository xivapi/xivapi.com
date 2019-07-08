<?php

namespace App\Command\Companion;

use App\Common\Command\CommandConfigureTrait;
use App\Service\Companion\Updater\MarketQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoQueueItemsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoQueueItemsCommand',
        'desc' => 'Auto queue items to update',
        'args' => [
            [ 'action', InputArgument::OPTIONAL, '(Optional) action to perform' ],
        ]
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
         *   php bin/console Companion_AutoQueueItemsCommand prioritise
         *   php bin/console Companion_AutoQueueItemsCommand untrack
         */
        switch ($input->getArgument('action')) {
            default:
                $this->marketQueue->queue();
                break;
                
            case 'prioritise':
                $this->marketQueue->rePrioritiseItems();
                break;
    
            case 'untrack':
                $this->marketQueue->untrackNonVisititems();
                break;
        }
        
    }
}
