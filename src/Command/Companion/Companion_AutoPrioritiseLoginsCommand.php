<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoPrioritiseLoginsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoPrioritiseLoginsCommand',
        'desc' => 'Pushes all tokens that have failed to login to the front of the queue',
    ];

    /** @var CompanionTokenManager */
    private $companionTokenManager;

    public function __construct(CompanionTokenManager $companionTokenManager, $name = null)
    {
        $this->companionTokenManager = $companionTokenManager;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionTokenManager->reprioritise();
    }
}
