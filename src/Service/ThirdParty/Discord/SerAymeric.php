<?php

namespace App\Service\ThirdParty\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SerAymeric
{
    const ENDPOINT = 'https://mog.xivapi.com/aymeric';

    private function send(string $endpoint, array $json)
    {
        (new Client())->post(self::ENDPOINT . $endpoint, [
            RequestOptions::JSON => $json,
            RequestOptions::QUERY => [
                'key' => getenv('DISCORD_BOT_USAGE_KEY')
            ],
        ]);
    }

    /**
     * Send a message to a user
     */
    public function sendMessage(string $message, string $userId)
    {
        $this->send('/say', [
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Send a embed to a user
     */
    public function sendEmbed(array $embed, string $userId)
    {
        $this->send('/embed', [
            'user_id' => $userId,
            'embed'   => $embed,
        ]);
    }
}
