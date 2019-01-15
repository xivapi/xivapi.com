<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCompanionAppLoginCommand extends Command
{
    use CommandHelperTrait;
    
    protected function configure()
    {
        $this
            ->setName('UpdateCompanionAppLoginCommand')
            ->setDescription('Re-login to each character')
            ->addArgument('account', InputArgument::REQUIRED, 'Which account to login to, A or B')
            ->addArgument('server', InputArgument::OPTIONAL, 'Run a specific server')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new CompanionTokenManager();
        $manager->setSymfonyStyle(
            new SymfonyStyle($input, $output)
        );
        
        $accounts = [
            'A' => 'COMPANION_APP_ACCOUNT_A',
            'B' => 'COMPANION_APP_ACCOUNT_B',
            'C' => 'COMPANION_APP_ACCOUNT_C',
        ];

        // grab account and process logins, go, go, go!
        $server  = $input->getArgument('account');
        $account = $accounts[$input->getArgument('account')];

        $manager->go($account, $server);
    }
}
