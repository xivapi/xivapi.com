<?php

namespace App\Tests\Custom;

require_once __DIR__.'/../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class JsonPayloadTest
{
    public function __construct()
    {
        $this->testGenericJsonPayload();
    }

    public function testGenericJsonPayload()
    {
        $guzzle = new Client();

        $response = $guzzle->get('https://xivapi.com/RecipeNotebookList', [
            RequestOptions::JSON => [
                'key' => 'testing',
                'limit' => 3,
                'columns_all' => 1,
            ]
        ]);

        $json = json_decode($response->getBody());

        file_put_contents(__DIR__.'/test.json', \GuzzleHttp\json_encode($json, JSON_PRETTY_PRINT));
    }
}

new JsonPayloadTest();
