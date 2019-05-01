<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
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
            [ 'action', InputArgument::REQUIRED, '(Optional) action to perform' ],
            [ 'login', InputArgument::OPTIONAL, '(Optional) provide an account + server' ],
        ]
    ];

    /** @var CompanionTokenManager */
    private $ctm;

    public function __construct(CompanionTokenManager $ctm, $name = null)
    {
        $this->ctm = $ctm;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console Companion_AutoLoginAccountsCommand login MB1,Phoenix
         * php bin/console Companion_AutoLoginAccountsCommand auto_login
         * php bin/console Companion_AutoLoginAccountsCommand auto_login_all
         * php bin/console Companion_AutoLoginAccountsCommand update_characters MB1
         */
        switch ($input->getArgument('action')) {
            default:
                $output->writeln('Unknown, must be: login, auto_login, update_characters');
                break;
    
            case 'login':
                [$account, $server] = explode(',', $input->getArgument('login'));
                $this->ctm->login($account, $server);
                break;
                
            case 'auto_login':
                $this->ctm->autoLoginToExpiringAccount();
                break;

            case 'auto_login_all':
                $this->ctm->autoLoginToAllAccounts();
                break;

            case 'update_characters':
                $this->ctm->autoPopulateCharacters(
                    $input->getArgument('login')
                );
        }
    }
}
