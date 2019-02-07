<?php

namespace App\Command\Misc;

use App\Service\Redis\Redis;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SharpenHighResLodestoneIconsCommand extends Command
{
    const FOLDER = __DIR__ .'/../../../public/i2/ls2';
    const BORDER = __DIR__ .'/../../../public/i2/borders/rarity%s.png';
    
    protected function configure()
    {
        $this
            ->setName('SharpenHighResLodestoneIconsCommand')
            ->setDescription('Sharpen the HQ Lodestone Icons')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new ConsoleOutput();
    
        $manager = new ImageManager(['driver' => 'imagick']);
        $images = scandir(self::FOLDER);
        $total  = count($images);
        $console->writeln("Icons: ". count($images));

        $section = $console->section();
        foreach ($images as $i => $filename) {
            if (stripos($filename, '.png') === false) {
                continue;
            }
            
            $id   = str_ireplace('.png', null, $filename);
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            
            if ($item->Rarity == 3) {
                continue;
            }
            
            $img = self::FOLDER . "/" . $filename;
            $section->overwrite("[{$i} / {$total}] {$img}");
            
            /*
            // Add a border and sharpen image
            $img = $manager->make($img);
            $img->insert(
                $manager->make(sprintf(self::BORDER, $item->Rarity))
            );
            //$img->sharpen(12);
            $img->save(self::FOLDER . "/" . $filename);
            */
            
            // compress image
            $img = imagecreatefrompng(self::FOLDER . "/" . $filename);
            imagejpeg($img, self::FOLDER . "/" . $filename, 90);
        }
    }
}
