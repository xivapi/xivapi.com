<?php

namespace App\Service\DataCustom;

use App\Service\Common\Language;
use App\Service\Content\DescriptionFormatter;
use App\Service\Content\ManualHelper;
use App\Service\Redis\Redis;

class ItemDescriptions extends ManualHelper
{
    const PRIORITY = 20;
    
    /**
     * todo - Process using: https://github.com/viion/ffxiv-datamining/blob/master/research/item_actions.md
     */
    public function handle()
    {
        $ids = Redis::Cache()->get('ids_Item');
        $this->io->progressStart(count($ids));
        $formatter = new DescriptionFormatter();
        
        foreach ($ids as $id) {
            $this->io->progressStart();
            $key = "xiv_Item_{$id}";
            $item = $this->redis->get($key);
            
            if (empty($item->Description_en)) {
                continue;
            }
    
            foreach (Language::LANGUAGES as $lang) {
                if (!isset($object->{'Description_'. $lang})) {
                    $item->{'Description_'. $lang}     = null;
                    $item->{'DescriptionJSON_'. $lang} = null;
                    continue;
                }
    
                // format descriptions
                [$json, $desc] = $formatter->format($item->{'Description_'. $lang});
                $item->{'Description_'. $lang}     = $desc;
                $item->{'DescriptionJSON_'. $lang} = $json;
            }
            
            // save
            $this->redis->set($key, $item, self::REDIS_DURATION);
        }
        
        $this->io->progressFinish();
    }
}
