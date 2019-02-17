<?php

namespace App\Command\Companion;

use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoLoginAccountsCommand extends Command
{
    const NAME = 'Companion_AutoLoginAccountsCommand';
    const DESCRIPTION = 'Re-login to each character to obtain a companion token';

    /** @var CompanionTokenManager */
    private $companionTokenManager;

    public function __construct(CompanionTokenManager $companionTokenManager, $name = null)
    {
        $this->companionTokenManager = $companionTokenManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument('action', InputArgument::OPTIONAL, '(Optional) Either a list of servers or an account.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($action = $input->getArgument('action')) {
            
            if (in_array($action, CompanionTokenManager::SERVERS_ACCOUNTS)) {
                $this->companionTokenManager->account($action);
                return;
            }

            // loop through supplied servers, THEY MUST BE ON SAME ACC
            foreach (explode(',', $action) as $server) {
                $this->companionTokenManager->login($server);
            }
            
            return;
        }
        
        $this->companionTokenManager->auto();
    }
}
