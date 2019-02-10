<?php

namespace App\Service\Japan;

use App\Service\Redis\Redis;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Japan
{
    const ENDPOINT = 'http://lodestone.xivapi.com';
    //const ENDPOINT = 'http://xivapi.local';

    /**
     * Query the japan server
     */
    public static function query($uri, $query)
    {
        $key = __METHOD__ . sha1($uri . implode(',', $query));
        if ($data = Redis::Cache()->get($key)) {
            return $data;
        }

        $client = new Client([
            'base_uri' => self::ENDPOINT,
            'timeout'  => 15,
        ]);

        $res = $client->get($uri, [
            RequestOptions::QUERY => $query
        ]);

        $data = json_decode((string)$res->getBody());
        Redis::Cache()->set($key, $data, (60*60*3));
        return $data;
    }
}
