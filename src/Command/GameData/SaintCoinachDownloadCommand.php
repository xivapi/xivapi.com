<?php

namespace App\Command\GameData;

use App\Command\CommandHelperTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github\Client;

class SaintCoinachDownloadCommand extends Command
{
    use CommandHelperTrait;
    
    protected function configure()
    {
        $this
            ->setName('SaintCoinachDownloadCommand')
            ->setDescription('Connect to GitHub and check for updated builds of SaintCoinach. Downloads and extracts a build.')
            ->addArgument('fast', InputArgument::OPTIONAL, 'Fast command, skips questions')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->title('SAINT COINACH BUILD CHECKER');
        $this->startClock();
        $this->download();
        $this->endClock();
    }
    
    /**
     * Request the latest version from GitHub
     */
    private function download()
    {
        $this->io->text('Downloading SaintCoinach');
        
        // grab the latest release
        $release  = (new Client())->api('repo')->releases()->latest('ufx', 'SaintCoinach');
        $buildTag = $release['tag_name'];
        $this->io->text("Latest build: <info>{$buildTag}</info>");
    
        // start download
        $this->startDownloads($release);
    }
    
    /**
     * Download some files!
     */
    private function startDownloads($release)
    {
        $this->io->section('Downloads');
        $this->io->text('Which file to download?');
    
        foreach ($release['assets'] as $i => $build) {
            $this->io->text("- [{$i}] {$build['name']}");
        }
    
        $number = $this->input->getArgument('fast')
            ? 1 : $this->io->ask('(Enter the number to download)', 1);
    
        if (!isset($release['assets'][$number])) {
            $this->io->error('No file for that number... Try again');
            return;
        }
    
        $build = $release['assets'][$number];
        $filename = $build['name'];
        $download = $build['browser_download_url'];
        
        $this->io->text("Downloading: <info>{$filename}</info>");
        file_put_contents(
            __DIR__ . '/../../' . $filename,
            file_get_contents($download)
        );
        $this->complete();

        // check what type we downloaded
        if (stripos($filename, 'SaintCoinach.Cmd') !== false) {
            $this->runSaintCommand($filename);
            return;
        }
    }
    
    /**
     * Unzip saint command
     */
    private function runSaintCommand($filename)
    {
        $this->io->section('Extracting and running SaintCoinach.cmd');
        
        $filename = __DIR__ . '/../../' . $filename;
        $folder = __DIR__ . '/../xivapi.com/' . getenv('GAME_TOOLS_DIRECTORY') .'/SaintCoinach.Cmd';
        
        $zip = new \ZipArchive;
        $result = $zip->open($filename);
        
        if ($result === true) {
            $zip->extractTo($folder);
            $zip->close();
        }
        
        if (!is_dir($folder)) {
            $this->io->error('Failed to extract');
        }

        // todo - these should use the env variable for game path
        // generate bat scripts
        $this->io->text('Generating bat scripts: allrawexd, ui, bgm, maps');
        file_put_contents(
            $folder .'/extract-allrawexd.bat',
            'SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" allrawexd /UseDefinitionVersion'
        );
        file_put_contents(
            $folder .'/extract-ui.bat',
            'SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" ui /UseDefinitionVersion'
        );
        file_put_contents(
            $folder .'/extract-bgm.bat',
            'SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" bgm /UseDefinitionVersion'
        );
        file_put_contents(
            $folder .'/extract-maps.bat',
            'SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" maps /UseDefinitionVersion'
        );
        $this->complete();
        
        // save ex.json file
        $this->io->text('Updating local "ex.json" file');
        file_put_contents(
            __DIR__ . '/resources/ex.json',
            file_get_contents($folder .'/ex.json')
        );
        $this->complete();
        
        // info
        $exe = $folder .'/SaintCoinach.Cmd.exe';
        $this->io->text('Exe located: '. $exe);
        $this->io->text('Open SaintCoinach.Cmd.exe and run the commands you want.');
        $this->io->text('Then run: php bin/console app:data:copy');
        $this->complete();
    }
}
