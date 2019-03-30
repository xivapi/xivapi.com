<?php

namespace App\Command\Misc;

use App\Service\Redis\Redis;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Pre Waifu2x
 */
class LodestoneIcons_AddCornersCommand extends Command
{
    const FOLDER = __DIR__ .'/../../../public/i2/ls';
    const BORDER = __DIR__ .'/../../../public/i2/borders/rarity%s.png';
    
    protected function configure()
    {
        $this->setName('LodestoneIcons_AddCornersCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new ConsoleOutput();
        $manager = new ImageManager(['driver' => 'imagick']);
        $images  = scandir(self::FOLDER);
        $total   = count($images);
        $console->writeln("Icons: ". count($images));

        $section = $console->section();
        foreach ($images as $i => $filename) {
            if (stripos($filename, '.png') === false) {
                continue;
            }
            
            $id   = str_ireplace('.png', null, $filename);
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            
            $img = self::FOLDER . "/" . $filename;
            $section->overwrite("[{$i} / {$total}] {$img}");
            
            // Add a border and sharpen image
            $img = $manager->make($img);
            $img->insert(
                $manager->make(sprintf(self::BORDER, $item->Rarity))
            );
            $img->save(self::FOLDER . "/" . $filename);
            
            // compress image
            $img = imagecreatefrompng(self::FOLDER . "/" . $filename);
            imagejpeg($img, self::FOLDER . "/" . $filename, 95);
        }
    }
}
