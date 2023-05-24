<?php

namespace App\Service\Content;

use App\Command\GameData\SaintCoinachRedisCommand;
use App\Service\SaintCoinach\SaintCoinach;
use App\Common\Service\Redis\Redis;
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
        return Redis::Cache(true)->get("ids_{$contentName}");
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
}
