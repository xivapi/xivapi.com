<?php

namespace App\Service\ThirdParty;

use App\Service\Common\Arrays;
use DigitalOceanV2\Adapter\GuzzleHttpAdapter;
use DigitalOceanV2\DigitalOceanV2;
use DigitalOceanV2\Entity\Droplet;

class DigitalOcean
{
    /**
     * Get DigitalOcean costs
     */
    public static function costs()
    {
        $adapter = new GuzzleHttpAdapter(getenv('DIGITALOCEAN_TOKEN_ID'));
        $do      = new DigitalOceanV2($adapter);
        
        /** @var Droplet[] $droplets */
        $droplets = $do->droplet()->getAll();
        
        $list  = [];
        $total = 0.00;

        foreach($droplets as $droplet) {
            $total += (float)$droplet->size->priceMonthly;
            
            $list[] = [
                'os'        => $droplet->image->name,
                'ram'       => $droplet->memory,
                'location'  => $droplet->region->name,
                'cost'      => $droplet->size->priceMonthly,
                'name'      => $droplet->name
            ];
        }
    
        Arrays::sortBySubKey($list, 'name', true);
    
        return [
            'servers' => $list,
            'total'   => $total,
        ];
    }
}
