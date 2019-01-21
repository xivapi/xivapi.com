<?php

namespace App\Command\GameData;

use App\Command\CommandHelperTrait;
use App\Service\Redis\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Data\FileSystem;
use App\Service\Data\FileReader;
use App\Service\DataCustom\Pre\PreHandler;

class SaintCoinachClearRedisCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('SaintCoinachClearRedisCommand')
            ->setDescription('Clear all data.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->title('CLEAN REDIS DB');
        $this->startClock();

        // delete redis cache
        $this->io->text('Deleting redis cache');
        Redis::Cache()->flush();
        
        $this->complete();
    }
}
