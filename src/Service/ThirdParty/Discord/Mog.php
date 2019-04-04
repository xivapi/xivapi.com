<?php

namespace App\Service\ThirdParty\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Mog
{
    const ENDPOINT = 'https://mog.xivapi.com/mog/notify';

    private function send(array $json = null)
    {
        (new Client())->post(self::ENDPOINT, [
            RequestOptions::JSON => $json,
            RequestOptions::QUERY => [
                'key' => getenv('DISCORD_BOT_USAGE_KEY')
            ],
        ]);
    }

    /**
     * Post a message via mog
     */
    public function sendMessage(int $channel = null, string $content = null, array $embed = null)
    {
        $this->send([
            'channel' => $channel,
            'content' => $content,
            'embed' => $embed
        ]);
    }
}
