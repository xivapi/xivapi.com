<?php

namespace App\Command\Companion;

use App\Command\CommandHelperTrait;
use App\Entity\CompanionToken;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCompanionAppLoginCommand extends Command
{
    use CommandHelperTrait;

    const NAME = 'UpdateCompanionAppLoginCommand';
    const DESCRIPTION = 'Re-login to each character to obtain a companion token';

    /** @var CompanionTokenManager */
    private $companionTokenManager;

    public function __construct(CompanionTokenManager $companionTokenManager, $name = null)
    {
        $this->ctm = $companionTokenManager;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument('account', InputArgument::REQUIRED, 'Which account to login to, A, B or C.')
            ->addArgument('server', InputArgument::OPTIONAL, 'Login to just a specific server')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionTokenManager->login($input->getArgument('account'), $input->getArgument('server'));
    }
}
