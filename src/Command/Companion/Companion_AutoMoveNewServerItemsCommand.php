<?php

namespace App\Command\Companion;

use App\Common\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionItemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoMoveNewServerItemsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'Companion_AutoMoveNewServerItemsCommand',
        'desc' => '',
    ];

    /** @var CompanionItemManager */
    private $cim;

    public function __construct(CompanionItemManager $cim, $name = null)
    {
        $this->cim = $cim;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cim->moveNewServerItemIds();
    }
}
