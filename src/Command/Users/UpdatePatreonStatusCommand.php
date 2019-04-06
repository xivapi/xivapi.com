<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\User\Users;
use App\Service\UserAlerts\UserAlertsTriggers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePatreonStatusCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'UpdatePatreonStatusCommand',
        'desc' => 'Updates the patreon status on users',
    ];

    /** @var Users */
    private $users;

    public function __construct(Users $users, $name = null)
    {
        $this->users = $users;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->users->checkPatreonTiers();
    }
}
