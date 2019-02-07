<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionMarketUpdater;
use App\Service\Companion\CompanionPriority;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateItemPricesCommand extends Command
{
    use CommandHelperTrait;

    const NAME = 'UpdateItemPricesCommand';
    const DESCRIPTION = 'Auto-update the item prices based on priority';

    /** @var CompanionMarketUpdater */
    private $companionMarketUpdater;

    public function __construct(CompanionMarketUpdater $companionMarketUpdater, $name = null)
    {
        $this->companionMarketUpdater = $companionMarketUpdater;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument('data_center', InputArgument::OPTIONAL, 'Data center to process')
            ->addArgument('priority', InputArgument::OPTIONAL, 'Process a priority queue')
            ->addArgument('item_id', InputArgument::OPTIONAL, 'Update a specific item');
            
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionMarketUpdater->process(
            $input->getArgument('data_center'),
            $input->getArgument('priority'),
            $input->getArgument('item_id')
        );
    }
}
