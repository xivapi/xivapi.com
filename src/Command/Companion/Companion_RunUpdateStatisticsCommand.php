<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionStatistics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_RunUpdateStatisticsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_RunUpdateStatisticsCommand',
        'desc' => 'Run statistics over the auto-update queues.',
    ];

    /** @var CompanionStatistics */
    private $companionStatistics;

    public function __construct(CompanionStatistics $companionStatistics, $name = null)
    {
        $this->companionStatistics = $companionStatistics;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console Companion_RunUpdateStatisticsCommand
         */
        $this->companionStatistics->run();
    }
}
