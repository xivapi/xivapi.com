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

class SaintCoinachJsonCacheCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('SaintCoinachJsonCacheCommand')
            ->setDescription('Converts all CSV files into JSON documents for easier access during the update stage.')
            ->addArgument('content', InputArgument::OPTIONAL, 'Process only a specific piece of content');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->title('SAINT COINACH CSV -> JSON');
        $this->startClock();

        // obtain version
        $this->checkVersion();
        $this->checkSchema();
        $files = FileSystem::list($this->version);
    
        // store a list of available content
        $content = [];
        foreach ($this->schema as $sheet) {
            $content[] = $sheet->sheet;
        }
        $this->io->text('Compiled content data');
  
        // save content
        asort($content);
        $content = array_values(array_filter($content));
        $this->io->text("Saving: ". count($content) ." content entries");
        Redis::Cache()->set('content', $content, SaintCoinachRedisCommand::REDIS_DURATION);
    
        // write out content data
        $data = [];
        $this->io->progressStart(count($files->raw) + count($files->gamedata));
        foreach($files as $type => $list) {
            foreach($list as $i => $filename) {
                $this->io->progressAdvance();
                
                if ($input->getArgument('content') && $input->getArgument('content') !== $filename) {
                    continue;
                }

                $data[] = $filename;

                // save data
                FileSystem::save(
                    $filename,
                    'json',
                    FileReader::open($this->version, $filename, $type === 'raw')
                );
            }
        }
        $this->io->progressFinish();

        // Do pre data customisation
        $this->io->text('Performing pre-data customisation');
        PreHandler::CustomDataConverter();
        $this->complete();
    }
}
