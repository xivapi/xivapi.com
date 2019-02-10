<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\PopulateCompanionMarketDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoPopulateItemsCommand extends Command
{
    use CommandHelperTrait;

    const NAME = 'Companion_AutoPopulateItemsCommand';

    /** @var PopulateCompanionMarketDatabase */
    private $companionMarketPopulator;

    public function __construct(PopulateCompanionMarketDatabase $companionMarketPopulator, $name = null)
    {
        $this->companionMarketPopulator = $companionMarketPopulator;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionMarketPopulator->populate();
    }
}
