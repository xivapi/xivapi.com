<?php

namespace App\Common\ServicesThirdParty\Discord;

use App\Common\Exceptions\BasicException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Mog
{
    const ENDPOINT           = 'https://mog.xivapi.com';
    const ENDPOINT_NOTIFY    = '/mog/notify';
    const ENDPOINT_DM        = '/mog/dm';
    const ENDPOINT_IS_PATRON = '/users/patreon-tier';
    const METHOD_POST        = 'POST';
    const METHOD_GET         = 'GET';

    private function send(
        string $method,
        string $endpoint,
        array $json = null,
        array $query = null
    ) {
        $query['key'] = getenv('DISCORD_BOT_USAGE_KEY');
        
        // prevent issues when key is missing?
        if (empty($query['key']) || strlen($query['key']) < 20) {
            throw new BasicException("Missing API key for Discord Mognet Bot");
        }

        $config = [
            'base_uri' => self::ENDPOINT,
            'timeout'  => 5
        ];

        return (new Client($config))->request($method, $endpoint, [
            RequestOptions::JSON => $json,
            RequestOptions::QUERY => $query
        ]);
    }

    /**
     * Post a message via mog
     */
    public function sendMessage(int $channel = null, string $content = null, array $embed = null)
    {
        $this->send(self::METHOD_POST, self::ENDPOINT_NOTIFY, [
            'channel' => $channel,
            'content' => $content,
            'embed' => $embed
        ]);
    }

    /**
     * Post a direct message via mog
     */
    public function sendDirectMessage(string $userId = null, string $content = null, array $embed = null)
    {
        $this->send(self::METHOD_POST, self::ENDPOINT_DM, [
            'user_id' => $userId,
            'content' => $content,
            'embed' => $embed
        ]);
    }

    /**
     * Get a user role
     */
    public function getUserRole(int $userId)
    {
        $response = $this->send(self::METHOD_GET, self::ENDPOINT_IS_PATRON, [], [
            'user_id' => $userId,
        ]);

        return json_decode(
            $response->getBody()
        );
    }
}
