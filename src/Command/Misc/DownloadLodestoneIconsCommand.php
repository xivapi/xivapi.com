<?php

namespace App\Command\Misc;

use App\Command\CommandHelperTrait;
use App\Command\GameData\SaintCoinachRedisCommand;
use App\Service\Redis\Redis;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Lodestone\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadLodestoneIconsCommand extends Command
{
    use CommandHelperTrait;

    // store our data so we don't have to re-download everything.
    const SAVED_LIST_FILENAME = __DIR__ . '/db.json';
    // url to grab lodestone info from, we can get this from the market
    const XIVAPI_MARKET_URL = '/market/phoenix/items/%s';
    // url to companion icon, which is a bit smaller than the lodestone one
    const COMPANION_ICON_URL = 'https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/%s.png';
    // path to icon directory
    const ICON_DIRECTORY = __DIR__.'/../../../public/i2/ls/';
    
    /** @var Client */
    private $guzzle;
    /** @var Api */
    private $lodestone;
    /** @var array */
    private $exceptions = [];
    /** @var int */
    private $saved = 0;
    /** @var array */
    private $completed = [];
    
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        
        $this->guzzle    = new Client([ 'base_uri' => 'https://xivapi.com' ]);
        $this->lodestone = new Api();
    }
    
    protected function configure()
    {
        $this
            ->setName('DownloadLodestoneIconsCommand')
            ->setDescription('Downloads a bunch of info from Lodestone, including icons.')
            ->addArgument('item_id', InputArgument::OPTIONAL, 'Test Item');
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title('Lodestone Icon Downloader');
    
        $ids   = Redis::Cache()->get('ids_Item');
        $total = count($ids);
        $test  = $input->getArgument('item_id');
        
        // load out completed list
        $this->loadCompleted();
        
        $this->io->section('Downloading icons');
        $this->io->progressStart(count($ids));
        foreach ($ids as $i => $itemId) {
            $this->io->progressAdvance();
            
            // skip non test ones
            if ($test && $test != $itemId) {
                continue;
            }
            
            // if we've completed, skip it
            if ($test == null && in_array($itemId, $this->completed)) {
                continue;
            }

            // grab lodestone market data
            $lodestoneMarket = $this->getLodestoneMarketData($itemId);

            // skip if no lodestone id
            if (!isset($lodestoneMarket->LodestoneId)) {
                $this->markComplete(false, 'No lodestone ID', $itemId);
                continue;
            }
            
            // fix url on lodestone market url
            $lodestoneMarket->Icon   = empty($lodestoneMarket->Icon) ? null : sprintf(self::COMPANION_ICON_URL, $lodestoneMarket->Icon);
            $lodestoneMarket->IconHq = empty($lodestoneMarket->IconHq) ? null : sprintf(self::COMPANION_ICON_URL, $lodestoneMarket->IconHq);
            
            // parse db page for the "big" icon
            try {
                $lodestoneItem = $this->lodestone->getDatabaseItem($lodestoneMarket->LodestoneId);
            } catch (\Exception $ex) {
                $this->exceptions[$itemId] = $ex->getMessage();
                $this->markComplete(false, 'No lodestone database item icon', $itemId, $lodestoneMarket);
                continue;
            }
            
            // skip if it fields
            if ($lodestoneItem == null || empty($lodestoneItem->Icon)) {
                $this->markComplete(false, 'No lodestone database item icon', $itemId);
                continue;
            }
            
            // download both icons
            #$this->downloadIcon($lodestoneMarket->Icon, __DIR__.'/Icons/Companion/', $itemId);
            #$this->downloadIcon($lodestoneMarket->IconHq, __DIR__.'/Icons/CompanionHq/', $itemId);
            $this->downloadIcon($lodestoneItem->Icon, __DIR__.'/Icons/Lodestone/', $itemId);
            
            // save
            $this->markComplete(true, 'Download OK', $itemId, $lodestoneMarket, $lodestoneItem);
        }
        $this->io->progressFinish();

        // print exceptions
        $this->io->text(count($this->exceptions) . ' exceptions were recorded.');
        foreach ($this->exceptions as $itemId => $error) {
            $this->io->text("Exception: {$itemId} = {$error}");
        }
        
        // print saved total
        $this->io->text([ ' ', "Saved: {$this->saved} icons" ]);
        
        // copy icons
        $this->io->section('Copying files');
        $this->io->progressStart(count($ids));
        foreach ($ids as $i => $itemId) {
            $file = __DIR__."/Icons/Lodestone/{$itemId}.png";
            $this->io->progressAdvance();
            
            if (file_exists($file)) {
                copy($file, self::ICON_DIRECTORY . $itemId . ".png");
            }
        }
        $this->io->progressFinish();
    }
    
    /**
     * Load the item ids that have been completed.
     */
    private function loadCompleted()
    {
        $this->io->text('Loading the complete list');
        $saved = file_get_contents(self::SAVED_LIST_FILENAME);
        $saved = json_decode($saved);
        
        foreach ($saved as $itemId => $info) {
            $this->completed[] = $itemId;
        }
        
        $this->complete();
        unset($saved);
    }
    
    /**
     * marks an item as complete
     */
    private function markComplete($status, $message, $itemId, $lodestoneMarket = null, $lodestoneItem = null)
    {
        // load current saved list
        $saved = file_get_contents(self::SAVED_LIST_FILENAME);
        $saved = json_decode($saved);
        
        // append item
        $saved->{$itemId} = [
            'ItemID'          => $itemId,
            'Status'          => $status,
            'Message'         => $message,
            'LodestoneMarket' => $lodestoneMarket,
            'LodestoneItem'   => $lodestoneItem,
        ];
        
        // re save
        file_put_contents(self::SAVED_LIST_FILENAME, json_encode($saved, JSON_PRETTY_PRINT));
    }
    
    /**
     * Download the icons
     */
    private function downloadIcon($url, $path, $filename)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        
        if (empty($url)) {
            return;
        }
        
        copy($url . "?t=" . time(), $path . $filename . ".png");
        $this->saved++;
    }
    
    /**
     * Grab lodestone data from Market API
     */
    private function getLodestoneMarketData(int $itemId): ?\stdClass
    {
        try {
            $request = $this->guzzle->get(sprintf(self::XIVAPI_MARKET_URL, $itemId), [
                RequestOptions::QUERY => [
                    'key' => 'testing'
                ]
            ]);
    
            $item = json_decode($request->getBody());
        } catch (\Exception $ex) {
            $this->exceptions[$itemId] = $ex;
            return null;
        }
        
        return $item->Lodestone;
    }
}
