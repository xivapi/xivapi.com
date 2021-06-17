<?php

namespace App\Service\SaintCoinach;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Downloader;
use App\Service\Data\FileSystem;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;

class SaintCoinach
{
    const REDIS_DURATION = (60 * 60 * 24 * 365 * 10); // 10 years
    const SCHEMA_FILENAME  = ROOT . '/data/SaintCoinach.Cmd/ex.json';
    const SCHEMA_DIRECTORY = ROOT . '/data/SaintCoinach.Cmd';
    const DOCUMENTS_FOLDER = ROOT . '/data/gamedocuments/';
    const SAVE_PATH = ROOT .'/data/';
    
    /** @var ConsoleOutput */
    private $console;
    
    public function __construct()
    {
        $this->console = new ConsoleOutput();
    }
    
    /**
     * Download a new copy of SaintCoinach
     */
    public function download()
    {
        $this->console->writeln('Downloading SaintCoinach.Cmd');
    
        // grab the latest release from github
        $release  = (new Client())->api('repo')->releases()->latest('xivapi', 'SaintCoinach');
        $buildTag = $release['tag_name'];
        $this->console->writeln("Latest build: <info>{$buildTag}</info>");

        // check for SaintCoinach.Cmd release
        $build = $release['assets'][1] ?? false;
        if ($build === false) {
            throw new \Exception('Could not find Saint Coinach cmd release at Download Position 1');
        }

        // Download
        $download = $build['browser_download_url'];
        $filename = self::SAVE_PATH . 'SaintCoinach.Cmd.zip';
        $this->console->writeln("Downloading: <info>{$download}</info>");
        $this->console->writeln("Save Path: <info>{$filename}</info>");
        
        Downloader::save($download, $filename);
        
        // extract it
        $this->console->writeln("Extracting: <info>{$filename}</info>");
        $extractFolder = self::SAVE_PATH . 'SaintCoinach.Cmd';
        
        $zip = new \ZipArchive;
        $zip->open($filename);
        $zip->extractTo($extractFolder);
        $zip->close();
        
        $this->console->writeln('Generating Bat Scripts');
        $this->generateBatScript($extractFolder, 'allrawexd');
        $this->generateBatScript($extractFolder, 'ui');
        $this->generateBatScript($extractFolder, 'bgm');
        $this->generateBatScript($extractFolder, 'maps');
        
        // ensure unix line endings
        $this->console->writeln("Fixing line endings...");
        shell_exec('dos2unix '. self::SCHEMA_DIRECTORY . '/Definitions/*');
        $this->console->writeln("Complete");
        
        // build schema into 1 file
        $this->console->writeln("Building single schema");
        $schema = [];
        $contentNames = [];
        
        foreach (scandir(self::SCHEMA_DIRECTORY . '/Definitions') as $file) {
            $fileinfo = pathinfo($file);
            
            if ($fileinfo['extension'] === 'json') {
                $this->console->writeln($file);
                $schema[] = json_decode(file_get_contents(self::SCHEMA_DIRECTORY . '/Definitions/'. $file), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->console->writeln("! There was a JSON_DECODE error with: {$file} -- code: ". json_last_error() . " -- msg: ". json_last_error_msg());
                }
    
                $contentNames[] = pathinfo($file)['filename'];
            }
        }
        
        // store content names
        $contentNames = array_values(array_filter($contentNames));
        Redis::Cache()->set('content', $contentNames, SaintCoinach::REDIS_DURATION);
        $this->console->writeln("Content definition list updated");
        
        // store schema
        $schema  = array_values(array_filter($schema));
        $version = trim(file_get_contents(self::SCHEMA_DIRECTORY . '/Definitions/game.ver'));
        file_put_contents(self::SCHEMA_FILENAME, json_encode([
            'version' => $version,
            'sheets' => $schema
        ], JSON_PRETTY_PRINT));
        $this->console->writeln("Defintion ex.json file rebuilt");

        $this->console->writeln('Finished');
    }
    
    /**
     * Get the JSON Schema from SaintCoinach
     */
    public static function schema()
    {
        if (!file_exists(self::SCHEMA_FILENAME)) {
            throw new \Exception("SaintCoinach schema ex.json file missing at: ". self::SCHEMA_FILENAME);
        }
        
        $schema = \GuzzleHttp\json_decode(
            file_get_contents(self::SCHEMA_FILENAME)
        );
        
        return $schema;
    }
    
    /**
     * Get the current extracted schema version, this is
     * the version from the folder, not the one in ex.json
     */
    public static function version()
    {
        $dirs = glob(self::SCHEMA_DIRECTORY . '/*' , GLOB_ONLYDIR);

        // remove non version names
        foreach ($dirs as $i => $dir) {
            $versions = explode('.', basename($dir));
            
            if (count($versions) < 3) {
                unset($dirs[$i]);
            }
        }
        
        // there should only be 1, if not, throw exception to sort this
        if (count($dirs) > 1) {
            throw new \Exception("there is more than 1 directory in the SaintCoinach
                extracted location, delete old extractions");
        }
        
        return str_ireplace([self::SCHEMA_DIRECTORY, '/'], null, $dirs[0]);
    }
    
    /**
     * Return the data directory for where stuff is extracted
     */
    public static function directory()
    {
        return self::SCHEMA_DIRECTORY ."/". self::version();
    }
    
    /**
     * Get files in latest raw exd saint files
     */
    public static function rawExdFiles()
    {
        return FileSystem::list();
    }
    
    /**
     * Generate a windows bat script that runs a command via SaintCoinach.Cmd
     */
    private function generateBatScript($extractFolder, $command)
    {
        file_put_contents(
            "{$extractFolder}/extract-{$command}.bat",
            sprintf(
                'SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" %s /UseDefinitionVersion',
                $command
            )
        );
    }
}
