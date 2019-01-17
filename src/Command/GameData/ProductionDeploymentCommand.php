<?php

namespace App\Command\GameData;

use App\Command\CommandHelperTrait;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductionDeploymentCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var Cache */
    private $redis;
    /** @var Cache */
    private $redisProduction;
    
    public function __construct()
    {
        parent::__construct();
    }
    
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
        $this->redis = new Cache();
        $this->redisProduction = (new Cache())->connect('REDIS_SERVER_PROD', true);

        $this->setSymfonyStyle($input, $output);
        $this->title('DEPLOY TO PRODUCTION (GAME DATA)');
        $this->startClock();
        
        $this->io->text(
            "Deploying to: {$this->redisProduction->config->ip}"
        );
    
        // start data deployment
        $redisKey = $input->getArgument('redis_key') ?: '*';
        $this->io->text('Fetching all redis keys ...');

        // deploy all keys
        $this->deployKeyList(
            $this->redis->keys($redisKey)
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
            $this->redisProduction->initPipeline();
            foreach ($keys as $key) {
                // ignore specific prefixes
                $prefix = explode('_', $key)[0];
                if (!in_array($prefix, $allowedPrefixes)) {
                    continue;
                }
            
                // set keys
                $this->redisProduction->set(
                    $key,
                    $this->redis->get($key),
                    SaintCoinachRedisCommand::REDIS_DURATION
                );
            }
    
            $this->redisProduction->execPipeline();
            $this->io->progressAdvance(count($keys));
        }
        $this->io->progressFinish();

        $this->complete();
    }
}
