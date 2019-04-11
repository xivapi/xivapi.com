<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\Redis\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WipeDailyQueryLimitCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'WipeDailyQueryLimitCommand',
        'desc' => 'Reset daily query limits',
    ];

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keys = Redis::Cache()->keys('api_key_request_count_*');

        foreach ($keys as $key) {
            Redis::Cache()->delete($key);
        }

        $output->writeln("Cleared all query limit keys.");
    }
}
