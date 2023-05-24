<?php

namespace App\Command\GameData;

use App\Command\CommandHelperTrait;
use App\Common\Service\Redis\Redis;
use App\Common\Utils\System;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Data\FileSystemCache;
use App\Service\Data\DataHelper;
use App\Service\Data\FileSystem;
use App\Service\GamePatch\Patch;

/**
 * This is a bit mad and to be replaced by: https://github.com/xivapi/xivapi-data
 */
class RedisDeployUpdateCommand extends Command
{
    use CommandHelperTrait;



    protected function configure()
    {
        $this
            ->setName('RedisDeployUpdateCommand')
            ->setDescription('Deploy content update from local to prod redis');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localRedis = Redis::cache(true);
        $prodRedis = Redis::cache(false);
        $keys = $localRedis->keys('*');
        $this->io->progressStart(count($keys));
        foreach ($keys as $key) {
            $prodRedis->set($key, $localRedis->get($key));
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
    }
}
