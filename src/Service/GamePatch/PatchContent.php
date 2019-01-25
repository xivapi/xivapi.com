<?php

namespace App\Service\GamePatch;

use App\Service\Content\ManualHelper;

/**
 * Tracks patch info for each piece of content
 */
class PatchContent extends ManualHelper
{
    const FILENAME = __DIR__ .'/content/%s.json';
    
    const TRACKED_CONTENT = [
        'Achievement',
        'Action',
        'Addon',
        'Balloon',
        'ClassJob',
        'Companion',
        'CompanyAction',
        'CraftAction',
        'Emote',
        'Fate',
        'InstanceContent',
        'Item',
        'Leve',
        'Mount',
        'ENpcResident',
        'BNpcName',
        'Orchestrion',
        'Pet',
        'PlaceName',
        'Quest',
        'Recipe',
        'SpecialShop',
        'Status',
        'Title',
        'Trait',
        'Weather',
        'TripleTriadCard',
    ];

    public function handle($single = null)
    {
        $this->updatePatchPersistence();
        $this->updatePatchContent($single);
    }
    
    private function updatePatchContent($single)
    {
        $this->io->section('Updating tracked content');
        
        $patchService = new Patch();
    
        $total = count(self::TRACKED_CONTENT);
        foreach (self::TRACKED_CONTENT as $i => $contentName) {
            $current = ($i+1);
            
            if ($single && $single != $contentName) {
                continue;
            }
            
            $this->io->text("{$current}/{$total} <comment>Tracked: {$contentName}</comment>");
            $ids = $this->redis->get("ids_{$contentName}");
            
            if (!$ids) {
                $this->io->text('No ids for: '. $contentName);
                continue;
            }
    
            // load patch file
            $json = file_get_contents(sprintf(self::FILENAME, $contentName));
            $json = json_decode($json, true);
            
            // process all content ids
            foreach ($ids as $contentId) {
                // grab the patchId for this contentId
                $patchId = $json[$contentId] ?? null;
    
                // grab content
                $key     = "xiv_{$contentName}_{$contentId}";
                $content = $this->redis->get($key);

                // set patch
                $content->GamePatchID   = $patchId;
                $content->GamePatch     = $patchService->getPatchAtID((int)$patchId);
                
                // re-save content
                $this->redis->set($key, $content, self::REDIS_DURATION);
            }
        }
        
        $this->io->text(['Completed tracked-content patch versions', '', '']);
    }
    
    /**
     * @throws \Exception
     */
    private function updatePatchPersistence()
    {
        $this->io->section('Updating persistent patch data');
        
        // latest patch
        $patch = (new Patch())->getLatest();
        
        $content = (array)$this->redis->get('content');
        $total   = count($content);
        $current = 0;
        foreach ($content as $contentName) {
            $current++;
            $this->io->text("{$current}/{$total} <comment>{$contentName}</comment>");
            $filename = sprintf(self::FILENAME, $contentName);
        
            // grab all content ids
            $ids    = $this->redis->get("ids_{$contentName}");
            $schema = $this->redis->get("schema_{$contentName}");
            
            if (!$schema) {
                $this->io->text('!!! Error: No schema for: '. $contentName);
                continue;
            }
            
            // no ids? skip
            if (!$ids) {
                continue;
            }
            
            // find a string column
            $stringColumn = null;
            
            if (isset($schema->ContentSchema->Name_en)) {
                $stringColumn = 'Name_en';
            } else {
                foreach ($schema->ContentSchema as $column => $value) {
                    if ($value === 'string' && strpos($column, '_en') !== false) {
                        $stringColumn = $column;
                        break;
                    }
                }
            }
            
            // grab previous patch values if they exist, otherwise start a new list
            $list = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
        
            // loop through all content ids
            foreach ($ids as $id) {
                // grab content
                $content = $this->redis->get("xiv_{$contentName}_{$id}");
                
                if (!isset($content->{$stringColumn})) {
                    continue;
                }
            
                // we only care about stuff without a blank name_en
                if ($stringColumn && strlen(trim($content->{$stringColumn})) < 2) {
                    continue;
                }
            
                // save previous patch if it exists, otherwise use new patch id
                $list[$id] = isset($list[$id]) ? $list[$id] : $patch->ID;
            }
        
            // save
            file_put_contents($filename, json_encode($list));
        }
    
        $this->io->text('Complete');
    }
}
