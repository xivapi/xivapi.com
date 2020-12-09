<?php

namespace App\Common\ServicesThirdParty\DigitalOcean;

use App\Common\Utils\Arrays;
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
        $total   = 0.00;

        /** @var Droplet $droplet */
        foreach($do->droplet()->getAll() as $droplet) {
            $total += (float)$droplet->size->priceMonthly;
            
            $servers[] = [
                'cost'      => $droplet->size->priceMonthly,
                'name'      => $droplet->name
            ];
        }

        /** @var Volume $volume */
        foreach ($do->volume()->getAll() as $i => $volume) {
            $total += (float)$volume->sizeGigabytes * 0.10;

            $volumes[] = [
                'name' => 'Vol 0'. ($i+1) .' '. $volume->sizeGigabytes .' GB',
                'cost' => (float)$volume->sizeGigabytes * 0.10,
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
