<?php

namespace App\Service\Content;

use App\Command\GameData\SaintCoinachRedisCommand;
use App\Service\Data\SaintCoinach;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\Data\CsvReader;

class ManualHelper
{
    const REDIS_DURATION = SaintCoinachRedisCommand::REDIS_DURATION;
    
    /** @var SymfonyStyle */
    public $io;
    /** @var Cache */
    public $redis;
    
    public function init(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->io->text('<info>'. get_class($this) .'</info>');
        $this->redis = new Cache();
        return $this;
    }
    
    /**
     * Get keys
     */
    public function getContentIds($contentName)
    {
        $key    = "ids_{$contentName}";
        $keys   = $this->redis->get($key);
        return $keys;
    }
    
    /**
     * Get a CSV
     */
    public function getCsv($file)
    {
        $path = SaintCoinach::directory() .'/raw-exd-all/';
        $file = $path . $file;
        
        if (!file_exists($file)) {
            return false;
        }
        
        $csv = CsvReader::Get($file);
        $csv = array_splice($csv, 2);
        return $csv;
    }
    
    public function pipeToRedis($data, $count = 100)
    {
        if (count($data) !== $count) {
            return $data;
        }
        
        $this->redis->initPipeline();
        foreach ($data as $key => $content) {
            $this->redis->set($key, $content, self::REDIS_DURATION);
        }
        $this->redis->execPipeline();
        
        unset($data);
        return [];
    }
}
