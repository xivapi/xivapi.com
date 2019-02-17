<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionPriority;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_CalculatePriorityCronJobsCommand extends Command
{
    use CommandHelperTrait;

    const NAME = 'Companion_CalculatePriorityCronJobsCommand';

    /** @var CompanionPriority */
    private $companionPriority;

    public function __construct(CompanionPriority $companionPriority, $name = null)
    {
        $this->companionPriority = $companionPriority;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionPriority->calculatePriorityCronJobs();
    }
}
