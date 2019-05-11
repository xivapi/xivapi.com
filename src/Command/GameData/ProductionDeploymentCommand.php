<?php

namespace App\Command\GameData;

use App\Command\CommandHelperTrait;
use App\Service\Redis\Redis;
use App\Service\Redis\RedisCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductionDeploymentCommand extends Command
{
    use CommandHelperTrait;
    
    protected function configure()
    {
        $this
            ->setName('ProductionDeploymentCommand')
            ->setDescription('Deploy all content data to live!')
            ->addArgument('redis_key', InputArgument::OPTIONAL, 'Deploy a specific redis key')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->title('DEPLOY TO PRODUCTION (GAME DATA)');
        $this->startClock();
        
        // start data deployment
        $redisKey = $input->getArgument('redis_key') ?: '*';
        $this->io->text('Fetching all redis keys ...');

        // deploy all keys
        $this->deployKeyList(
            Redis::Cache()->keys($redisKey)
        );

        $this->endClock();
    }
    
    /**
     * Deploy the actual data to production
     */
    private function deployKeyList($redisKeys)
    {
        $total = count($redisKeys);
        $this->io->text(number_format($total) ." keys to deploy");
    
        // begin
        $this->io->section('Deploying Data to Production');
        $this->io->progressStart($total);

        $allowedPrefixes = [
            'xiv',
            'xiv2',
            'xiv_korean',
            'xiv_chinese',
            'ids',
            'conn',
            'locale',
            'schema',
            'content',
            'patch',
            'character'
        ];
        
        foreach (array_chunk($redisKeys, 1000) as $keys) {
            // start a new pipeline
            Redis::Cache(RedisCache::PROD)->startPipeline();
            foreach ($keys as $key) {
                // ignore specific prefixes
                $prefix = explode('_', $key)[0];
                if (!in_array($prefix, $allowedPrefixes)) {
                    continue;
                }
            
                // set keys
                Redis::Cache(RedisCache::PROD)->set(
                    $key,
                    Redis::Cache()->get($key),
                    SaintCoinachRedisCommand::REDIS_DURATION
                );
            }
    
            Redis::Cache(RedisCache::PROD)->executePipeline();
            $this->io->progressAdvance(count($keys));
        }
        $this->io->progressFinish();

        $this->complete();
    }
}
