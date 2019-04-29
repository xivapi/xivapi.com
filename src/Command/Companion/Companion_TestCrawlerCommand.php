<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_TestCrawlerCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_TestCrawlerCommand',
        'desc' => 'Tests the crawler servers work',
    ];

    /** @var CompanionTokenManager */
    private $ctm;
    /** @var CompanionMarket */
    private $cm;

    public function __construct(CompanionTokenManager $ctm, CompanionMarket $cm, $name = null)
    {
        $this->ctm = $ctm;
        $this->cm = $cm;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Testing Crawler Access</comment>');

        $this->ctm->getCompanionTokens();
        $this->cm->get(46, 2);

        $output->writeln("If no errors, everything is OK");
    }
}
