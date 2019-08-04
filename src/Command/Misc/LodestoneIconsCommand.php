<?php

namespace App\Command\Misc;

use App\Entity\ItemIcon;
use App\Repository\ItemIconRepository;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\ImageManager;
use Lodestone\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use XIVAPI\XIVAPI;

class LodestoneIconsCommand extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em, ?string $name = null)
    {
        $this->em = $em;
        
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('LodestoneIconsCommand')
            ->setDescription('Downloads a bunch of info from Lodestone, including icons.')
            ->addArgument('item_id', InputArgument::OPTIONAL, 'Custom ID')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = date('Y-m-d H:i:s');
        $console = new ConsoleOutput();
        $console->writeln("Started: {$date}");
        $console->writeln("Downloading Icons");
        
        /** @var ItemIconRepository $repo */
        $repo = $this->em->getRepository(ItemIcon::class);
        
        /** @var $xivapi */
        $xivapi = new XIVAPI();
        $xivapiColumns = [
            'columns' => 'LodestoneID,eorzeadbItemId'
        ];
        
        /** @var Api $lodestone */
        $lodestone = new Api();
    
        /** @var ImageManager $manager */
        $manager = new ImageManager(['driver' => 'imagick']);
        
        //
        // Grab all item ids
        //
        $ids   = (array)Redis::Cache()->get('ids_Item');
        $total = number_format(count($ids));
        $console->writeln("Total Items: {$total}");

        //
        // Process items
        //
        $section = $console->section();
        foreach ($ids as $i => $itemId) {
            $i = ($i + 1);
            
            if ($input->getArgument('item_id') && $itemId != $input->getArgument('item_id')) {
                continue;
            }
            
            $section->overwrite("[{$i}] {$itemId}");
            
            // get entity or make a new one
            $entity = $repo->findOneBy([ 'item' => $itemId ]) ?: new ItemIcon();
            $entity->setItem($itemId);
            
            if ($entity->isComplete()) {
                continue;
            }
            
            // Ignore any item that fell into these states.
            $ignoreStatuses = [
                ItemIcon::STATUS_COMPLETE,
                ItemIcon::STATUS_NO_MARKET_ID,
                ItemIcon::STATUS_NO_LDS_ID,
            ];
            
            if (in_array($entity->getStatus(), $ignoreStatuses)) {
                continue;
            }

            /**
             * If we don't have a lodestone id, get it from market
             */
            if ($entity->getLodestoneId() == null) {
                /**
                 * Get market lodestone id
                 */
                $section->overwrite("[{$i}] {$itemId} - Getting lodestone id from XIVAPI Market");
                $market = $xivapi->queries($xivapiColumns)->market->item($itemId, ['Phoenix']);
                $market = $market->Phoenix;

                $section->overwrite("[{$i}] {$itemId} - Response: {$market->LodestoneID}");

                /**
                 * todo - this is temp until mogboard has been running for a few days
                 */
                if (empty($market->LodestoneID)) {
                    try {
                        $section->overwrite("[{$i}] {$itemId} - Getting lodestone id from Companion directly.");
                        $market = $xivapi->_private->itemPrices(
                            getenv('MB_ACCESS'),
                            $itemId,
                            'Phoenix'
                        );
                        
                        if (isset($market->Error) || empty($market->eorzeadbItemId)) {
                            throw new \Exception('Error or still empty');
                        }
    
                        $lodestoneId = $market->eorzeadbItemId;
                        $entity->setLodestoneId($lodestoneId);
                    } catch (\Exception $ex) {
                        $section->overwrite("[{$i}] {$itemId} - Item has no lodestone id");
                        $entity->setStatus(ItemIcon::STATUS_NO_LDS_ID);
                        $this->em->persist($entity);
                        $this->em->flush();
                        continue;
                    }
                }
                
                /**
                 * If empty, there is no market id yet
                 */
                /*
                if (empty($market->LodestoneID)) {
                    $section->overwrite("[{$i}] {$itemId} - No lodestone ID yet, need to wait");
                    $entity->setStatus(ItemIcon::STATUS_NO_MARKET_ID);
                    $this->em->persist($entity);
                    $this->em->flush();
                    continue;
                }
                
                $entity->setLodestoneId($market->LodestoneID);
                */
            }
            
            if (empty($entity->getLodestoneId())) {
                continue;
            }
    
            /**
             * If we don't have a lodestone icon url, parse it from lodestone
             */
            if ($entity->getLodestoneIcon() == null) {
                /**
                 * Parse lodestone
                 */
                try {
                    $lodestoneItem = $lodestone->getDatabaseItem($entity->getLodestoneId());
                    
                    if (empty($lodestoneItem->Icon)) {
                        throw new \Exception("Icon not found on lodestone page");
                    }
                    
                    $entity->setLodestoneIcon($lodestoneItem->Icon);
                } catch (\Exception $ex) {
                    $section->overwrite("[{$i}] {$itemId} - Lodestone Exception: {$ex->getMessage()}");
                    $entity->setStatus(ItemIcon::LODESTONE_EXCEPTION);
                    $this->em->persist($entity);
                    $this->em->flush();
                    continue;
                }
            }
    
            /**
             * Download the icon if we don't have it.
             */
            if (!is_dir(__DIR__."/../../../public/i2/ls_new/")) {
                mkdir(__DIR__."/../../../public/i2/ls_new/");
            }
            
            $saveFilename    = __DIR__."/../../../public/i2/ls/{$itemId}.png";
            $saveFilenameNew = __DIR__."/../../../public/i2/ls_new/{$itemId}.png";
            if (file_exists($saveFilename) === false) {
                $section->overwrite("[{$i}] {$itemId} - Download icon: ". $entity->getLodestoneIcon());
                copy($entity->getLodestoneIcon() . "?t=" . time(), $saveFilename);
                sleep(2);
                
                /**
                 * Add corners
                 */
                /*
                $section->overwrite("[{$i}] {$itemId} - Processing icon");
                $item = Redis::Cache()->get("xiv_Item_{$itemId}");
                $img = $manager->make($saveFilename);
    
                
                $section->overwrite("[{$i}] {$itemId} - Inserting rarity");
                $img->insert(
                    $manager->make(sprintf(__DIR__ .'/../../../public/i2/borders/rarity%s.png', $item->Rarity))
                );
                $img->save($saveFilename);
                */
    
                /**
                 * Compress image
                 */
                $section->overwrite("[{$i}] {$itemId} - Saving");
                $img = imagecreatefrompng($saveFilename);
                imagejpeg($img, $saveFilename, 95);
                
                /**
                 * Copy the file to "new"
                 */
                $section->overwrite("[{$i}] {$itemId} - Copying to new");
                copy($saveFilename, $saveFilenameNew);
            }
    
            $section->overwrite("[{$i}] {$itemId} - Complete");
            $entity->setStatus(ItemIcon::STATUS_COMPLETE);
            $this->em->persist($entity);
            $this->em->flush();
        }
        
        $console->writeln("Finished");
    }
}
