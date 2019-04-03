<?php

namespace App\Service\ThirdParty\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SerAymeric
{
    const ENDPOINT = 'https://mog.xivapi.com/aymeric/say';

    private function send(array $json)
    {
        (new Client())->post(self::ENDPOINT, [
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
        $this->send([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Send a embed to a user
     */
    public function sendEmbed(array $embed, string $userId)
    {
        $this->send([
            'user_id' => $userId,
            'embed'   => $embed,
        ]);
    }
}
