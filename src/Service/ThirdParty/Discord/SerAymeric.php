<?php

namespace App\Service\ThirdParty\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SerAymeric
{
    const ENDPOINT = 'https://mog.xivapi.com/aymeric/notify';
    
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
    public function sendMessage(string $userId, string $content = null, array $embed = null)
    {
        $this->send([
            'user_id' => $userId,
            'content' => $content,
            'embed' => $embed,
        ]);
    }
}
