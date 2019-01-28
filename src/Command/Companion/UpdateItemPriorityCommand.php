<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionPriority;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateItemPriorityCommand extends Command
{
    use CommandHelperTrait;

    const NAME = 'UpdateItemPriorityCommand';
    const DESCRIPTION = 'Update auto-update item priority for pricing.';

    /** @var CompanionPriority */
    private $companionPriority;

    public function __construct(CompanionPriority $companionPriority, $name = null)
    {
        $this->companionPriority = $companionPriority;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName(self::NAME)->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionPriority->calculate();
    }
}
