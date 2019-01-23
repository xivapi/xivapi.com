<?php

namespace App\Service\Common;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Mog
{
    /**
     * Post a message via mog
     */
    public static function send($message, $room = null)
    {
        $client = new Client();
        $client->post('https://mog.xivapi.com/say', [
            RequestOptions::QUERY => [
                'key' => getenv('DISCORD_BOT_USAGE_KEY')
            ],
            RequestOptions::JSON => [
                'message' => $message,
                'room' => $room
            ]
        ]);
    }
}
