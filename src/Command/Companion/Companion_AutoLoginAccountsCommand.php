<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionTokenManager;
use App\Service\Content\GameServers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoLoginAccountsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoLoginAccountsCommand',
        'desc' => 'Re-login to each character to obtain a companion token.',
        'args' => [
            [ 'action', InputArgument::OPTIONAL, '(Optional) Either a list of servers or an account.' ]
        ]
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
        $force = $input->getArgument('force') ?: false;
        
        /**
         * php bin/console Companion_AutoLoginAccountsCommand COMPANION_APP_ACCOUNT_A
         * php bin/console Companion_AutoLoginAccountsCommand COMPANION_APP_ACCOUNT_B
         *
         * php bin/console Companion_AutoLoginAccountsCommand Cerberus,Lich,Phoenix
         */
        if ($action = $input->getArgument('action')) {
            // if an account is provided
            if (in_array($action, CompanionTokenManager::SERVERS_ACCOUNTS)) {
                $this->companionTokenManager->account($action);
                return;
            }

            // if an data-center is provided
            if (array_key_exists(ucwords($action), GameServers::LIST_DC)) {
                $this->companionTokenManager->datacenter($action);
                return;
            }

            // loop through supplied servers, THEY MUST BE ON SAME ACC
            $output->writeln('If your servers are not on the same account, this will fail.');
            foreach (explode(',', $action) as $server) {
                $this->companionTokenManager->login($server);
            }
            
            return;
        }
        
        $output->writeln('Logging into the last logged in account.');
        $this->companionTokenManager->auto();
    }
}
