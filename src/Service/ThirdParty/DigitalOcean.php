<?php

namespace App\Service\ThirdParty;

use App\Service\Common\Arrays;
use DigitalOceanV2\Adapter\GuzzleHttpAdapter;
use DigitalOceanV2\DigitalOceanV2;
use DigitalOceanV2\Entity\Droplet;
use DigitalOceanV2\Entity\Volume;

class DigitalOcean
{
    /**
     * Get DigitalOcean costs
     */
    public static function costs()
    {
        $adapter = new GuzzleHttpAdapter(getenv('DIGITALOCEAN_TOKEN_ID'));
        $do      = new DigitalOceanV2($adapter);

        $servers = [];
        $volumes = [];
        $total = 0.00;

        /** @var Droplet $droplet */
        foreach($do->droplet()->getAll() as $droplet) {
            $total += (float)$droplet->size->priceMonthly;
            
            $servers[] = [
                'os'        => $droplet->image->name,
                'ram'       => $droplet->memory,
                'location'  => $droplet->region->name,
                'cost'      => $droplet->size->priceMonthly,
                'name'      => $droplet->name
            ];
        }

        /** @var Volume $volume */
        foreach ($do->volume()->getAll() as $volume) {
            $total += (float)$volume->sizeGigabytes * 0.10;

            $volumes[] = [
                'size'      => $volume->sizeGigabytes,
                'cost'      => (float)$volume->sizeGigabytes * 0.10,
                'location'  => $volume->region->name,
            ];
        }
    
        Arrays::sortBySubKey($servers, 'name', true);

        return [
            'servers' => $servers,
            'volumes' => $volumes,
            'total'   => $total,
        ];
    }
}
