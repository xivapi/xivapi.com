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
    public function sendMessage(string $message, int $channel = null)
    {
        $this->send([
            'channel' => $channel,
            'message' => $message
        ]);
    }

    /**
     * Post a message via mog
     */
    public function sendEmbed(array $embed, int $channel = null)
    {
        $this->send([
            'channel' => $channel,
            'embed'   => $embed
        ]);
    }
}
