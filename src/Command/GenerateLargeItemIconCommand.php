<?php

namespace App\Command;

use App\Service\Redis\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLargeItemIconCommand extends Command
{
    const SAVED_LIST_FILENAME = __DIR__.'/resources/icons.json';

    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('GenerateLargeItemIconCommand')
            ->setDescription('Downloads large icons from SE for items using the Companion API')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Lodestone Large Icon Downloader');

        $api = 'https://xivapi.com/market/phoenix/items/%s?key=testing';
        $url = 'https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/%s.png?%s';

        // redis cache
        $completed = json_decode(file_get_contents(self::SAVED_LIST_FILENAME));

        // loop through items
        $ids   = Redis::Cache()->get('ids_Item');
        $total = count($ids);
        $count = 0;
        foreach ($ids as $itemId) {
            $count++;

            // local filename
            $filename = __DIR__ ."/../../public/i2/{$itemId}.png";

            // Skip if file exists or we've previously completed it.
            if (file_exists($filename) || in_array($itemId, $completed)) {
                continue;
            }

            // grab market info as it includes item id
            // ... yes im a lazy shit; querying my own api
            $market = json_decode(file_get_contents(sprintf($api, $itemId)));

            // download if an icon exists
            if (!empty($market->Lodestone->Icon)) {
                // download icon and move it to local copy
                $iconUrl = sprintf($url, $market->Lodestone->Icon, time());

                // download icon
                copy($iconUrl, $filename);
            }

            // set secondary information
            $secondary = (Object)[
                'Icon2x'          => isset($filename) ? "/i2/{$itemId}.png" : null,
                'LodestoneID'     => $market->Lodestone->LodestoneId,
                'LodestoneIcon'   => $market->Lodestone->Icon,
                'LodestoneIconHQ' => $market->Lodestone->IconHq,
            ];
    
            Redis::Cache()->set("xiv2_Item_{$itemId}", $secondary, SaintCoinachRedisCommand::REDIS_DURATION);
            $this->io->text("{$count}/{$total} - Downloaded: {$market->Item->Name}");

            // save completed
            $completed[] = $itemId;
            file_put_contents(self::SAVED_LIST_FILENAME, json_encode($completed));
        }

    }
}
