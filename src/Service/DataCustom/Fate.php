<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class Fate extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        foreach ($this->getContentIds('Fate') as $id) {
            $key = "xiv_Fate_{$id}";
            $fate = $this->redis->get($key);
            
            // set icon
            $this->setIcon($fate);
    
            // save
            $this->redis->set($key, $fate, self::REDIS_DURATION);
        }
    }

    /**
     * Add icons
     */
    private function setIcon($fate)
    {
        // set icons
        $icons = [
            '0'     => '/f/060093.png',
            '60501' => '/f/060501.png',
            '60502' => '/f/060502.png',
            '60503' => '/f/060503.png',
            '60504' => '/f/060504.png',
            '60505' => '/f/060505.png',
        ];
        
        $num = $fate->IconMap;

        if ($num < 1) {
            $fate->Icon = '/f/fate.png';
            return;
        }
        
        // set icon
        $fate->Icon = $icons[$num] ?? '/f/fate.png';
    }
}
