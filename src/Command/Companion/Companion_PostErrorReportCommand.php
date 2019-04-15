<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionErrorHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_PostErrorReportCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_PostErrorReportCommand',
        'desc' => 'Post error report information to discord.',
    ];

    /** @var CompanionErrorHandler */
    private $companionErrorHandler;

    public function __construct(CompanionErrorHandler $companionErrorHandler, $name = null)
    {
        $this->companionErrorHandler = $companionErrorHandler;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console Companion_PostErrorReportCommand
         */
        $this->companionErrorHandler->report();
    }
}
