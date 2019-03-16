<?php

namespace App\Service\Content;

use App\Command\GameData\SaintCoinachRedisCommand;
use App\Service\SaintCoinach\SaintCoinach;
use App\Service\Redis\Redis;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\Data\CsvReader;

class ManualHelper
{
    const REDIS_DURATION = SaintCoinachRedisCommand::REDIS_DURATION;
    
    /** @var SymfonyStyle */
    public $io;
    
    public function init(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->io->text('<info>'. get_class($this) .'</info>');
        return $this;
    }
    
    /**
     * Get keys
     */
    public function getContentIds($contentName)
    {
        return Redis::Cache()->get("ids_{$contentName}");
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
        
        Redis::Cache()->startPipeline();
        foreach ($data as $key => $content) {
            Redis::Cache()->set($key, $content, self::REDIS_DURATION);
        }
        Redis::Cache()->executePipeline();
        
        unset($data);
        return [];
    }
}
