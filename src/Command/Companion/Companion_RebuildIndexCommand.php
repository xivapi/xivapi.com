<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionMarket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_RebuildIndexCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_RebuildIndexCommand',
        'desc' => 'Delete + Rebuild the Companion Elastic Search index.',
    ];
    
    /** @var CompanionMarket */
    private $companionMarket;
    
    public function __construct(CompanionMarket $companionMarket, ?string $name = null)
    {
        $this->companionMarket = $companionMarket;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Rebuilding index ...');
        $this->companionMarket->rebuildIndex();
        $output->writeln('Done.');
    }
}
