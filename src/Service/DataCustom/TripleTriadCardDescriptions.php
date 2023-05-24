<?php

namespace App\Service\DataCustom;

use App\Common\Utils\Language;
use App\Common\Service\Redis\Redis;
use App\Service\Content\DescriptionFormatter;
use App\Service\Content\ManualHelper;

class TripleTriadCardDescriptions extends ManualHelper
{
    const PRIORITY = 20;
    
    /**
     */
    public function handle()
    {
        $ids = Redis::Cache(true)->get('ids_TripleTriadCard');
        $formatter = new DescriptionFormatter();
        
        foreach ($ids as $id) {
            $key = "xiv_TripleTriadCard_{$id}";
            $object = Redis::Cache(true)->get($key);
            
            if (empty($object->Description_en)) {
                continue;
            }
    
            foreach (Language::LANGUAGES as $lang) {
                // ignore if already processed
                if (isset($object->{'DescriptionJSON_'. $lang})) {
                    continue;
                }
                
                if (!isset($object->{'Description_'. $lang})) {
                    $object->{'Description_'. $lang}     = null;
                    $object->{'DescriptionMale_'. $lang} = null;
                    $object->{'DescriptionJSON_'. $lang} = null;
                    continue;
                }
    
                // format descriptions
                [$json, $desc, $descMale] = $formatter->format($object->{'Description_'. $lang});
                $object->{'Description_'. $lang}     = $desc;
                $object->{'DescriptionMale_'. $lang} = $descMale;
                $object->{'DescriptionJSON_'. $lang} = $json;
            }
            
            // save
            Redis::Cache(true)->set($key, $object, self::REDIS_DURATION);
        }
    }
}
