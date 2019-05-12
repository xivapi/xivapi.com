<?php

namespace App\Command\GameData;

use App\Common\Command\CommandConfigureTrait;
use App\Service\SaintCoinach\SaintCoinach;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaintCoinachDownloadCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'SaintCoinachDownloadCommand',
        'desc' => 'Download Saint Coinach from GitHub',
    ];
 
    private $saintCoinach;
    
    public function __construct(SaintCoinach $saintCoinach, ?string $name = null)
    {
        $this->saintCoinach = $saintCoinach;
        
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->saintCoinach->download();
    }
}
