<?php

namespace App\Command\GameData;

use App\Common\Command\CommandConfigureTrait;
use App\Common\Service\Redis\Redis;
use App\Service\SaintCoinach\SaintCoinach;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Data\FileSystem;
use App\Service\Data\FileReader;
use App\Service\DataCustom\Pre\PreHandler;

class SaintCoinachJsonCacheCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'SaintCoinachJsonCacheCommand',
        'desc' => 'Converts all CSV files into JSON documents for easier access during the update stage.',
        'args' => [
            [ 'content', InputArgument::OPTIONAL, '(Optional) Process only a specific piece of content' ]
        ]
    ];
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new ConsoleOutput();
        
        // obtain version
        $files  = SaintCoinach::rawExdFiles();

        // store a list of available content
        $content = [];
        foreach (SaintCoinach::schema()->sheets as $sheet) {
            $content[] = $sheet->sheet;
        }
  
        // save content
        asort($content);

        $console->writeln('Saving content to Serialised Documents');
        $single = $input->getArgument('content');
        
        $total = count($files->gamedata) + count($files->raw);
        $count = 0;
        
        foreach($files as $type => $list) {
            foreach($list as $i => $filename) {
                $count++;
                
                // skip if we have a content argument
                if ($single && $single !== $filename) {
                    continue;
                }

                // save
                $console->writeln("- {$type} {$count}/{$total} :: {$filename}");

                $data = FileReader::open($filename, $type === 'raw');
                FileSystem::save($filename, 'json', $data);
            }
        }

        // Handle pre custom data modifications
        $console->writeln('Running custom converters');
        PreHandler::CustomDataConverter();
        $console->writeln('Finished');
    }
}
