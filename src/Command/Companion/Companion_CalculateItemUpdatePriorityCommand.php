<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionItemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_CalculateItemUpdatePriorityCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'Companion_CalculateItemUpdatePriorityCommand',
        'desc' => 'Automatically calculate the priority for cronjobs',
    ];

    /** @var CompanionItemManager */
    private $companionItemManager;

    public function __construct(CompanionItemManager $companionItemManager, $name = null)
    {
        $this->companionItemManager = $companionItemManager;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionItemManager->calculateItemUpdatePriority();
    }
}
