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

    /** @var CompanionTokenManager */
    private $ctm;

    public function __construct(CompanionTokenManager $ctm, $name = null)
    {
        $this->ctm = $ctm;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('UpdateCompanionAppLoginCommand')
            ->setDescription('Re-login to each character')
            ->addArgument('account', InputArgument::REQUIRED, 'Which account to login to, A, B or C.')
            ->addArgument('server', InputArgument::OPTIONAL, 'Login to just a specific server')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ctm->setSymfonyStyle(new SymfonyStyle($input, $output));
        $this->ctm->login(
            $input->getArgument('account'),
            $input->getArgument('server')
        );
    }
}
