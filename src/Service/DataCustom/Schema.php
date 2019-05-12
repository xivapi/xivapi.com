<?php

namespace App\Service\DataCustom;

use App\Common\Utils\Arrays;
use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Schema extends ManualHelper
{
    // always last
    const PRIORITY = 9999;
    
    public function handle()
    {
        $content = Redis::Cache()->get('content');
        $this->io->progressStart(count($content));
        
        foreach ($content as $contentName) {
            $this->io->progressAdvance();
            
            $schema = [
                'count'  => 0,
                'data'   => null,
            ];
    
            $ids = (array)Redis::Cache()->get("ids_{$contentName}");
            
            if (!$ids) {
                continue;
            }
            
            // pick a random one, sod it :D
            $id = $ids[array_rand($ids)];
            
            $content = Redis::Cache()->get("xiv_{$contentName}_{$id}");

            // count total fields
            $schemaObject       = json_decode(json_encode($content), true);
            $schemaCount        = count($schemaObject, COUNT_RECURSIVE);
            
            // if above max, process it
            if ($schemaCount > $schema['count']) {
                // build schema and columns
                $contentSchema  = Arrays::describeArray($schemaObject);
                $contentColumns = array_keys(Arrays::flattenArray($contentSchema));
                
                $schema = [
                    'count'  => $schemaCount,
                    'data'   => [
                        'ContentID'      => $id,
                        'ContentSchema'  => $contentSchema,
                        'ColumnCount'    => $schemaCount,
                        'Columns'        => $contentColumns
                    ],
                ];
            }
    
            // save
            Redis::Cache()->set("schema_{$contentName}", $schema['data'], self::REDIS_DURATION);
        }
    
        $this->io->progressFinish();
        $this->io->text('Schemas built for all content');
    }
}
