<?php

namespace App\WebSockets\BattleBar;

use Ratchet\ConnectionInterface;

/**
 * Handles all active clients
 */
class Clients
{
    /** @var \SplObjectStorage */
    private static $clients;
    /** @var array */
    private static $webClientToApiKey = [];
    /** @var array */
    private static $appClientToApiKey = [];

    public static function init()
    {
        self::$clients = new \SplObjectStorage;
    }

    public static function add(ConnectionInterface $client)
    {
        self::$clients->attach($client);
    }

    public static function remove(ConnectionInterface $client)
    {
        self::$clients->detach($client);
    }

    public static function hash(ConnectionInterface $client)
    {
        return self::$clients->getHash($client);
    }

    public static function get($hash)
    {
        return self::$clients[$hash];
    }

    public static function count()
    {
        return self::$clients->count();
    }
    
    public static function registerWebClient(ConnectionInterface $client, $apiKey)
    {
        self::$webClientToApiKey[$apiKey] = self::hash($client);
    }
    
    public static function registerAppClient(ConnectionInterface $client, $apiKey)
    {
        self::$appClientToApiKey[$apiKey] = self::hash($client);
    }
    
    /**
     * This will return both the web and app client for an API key, this
     * provides a bridge between the browser and the app.
     */
    public static function getClientsViaApiKey($apiKey): \stdClass
    {
        $web = self::$webClientToApiKey[$apiKey];
        $app = self::$appClientToApiKey[$apiKey];
        
        $web = self::$clients[$web] ?? null;
        $app = self::$clients[$app] ?? null;
        
        return (Object)[
            'web' => $web,
            'app' => $app
        ];
    }
    
    /**
     * Send any type of data to the client
     */
    public static function sendMessageToClient(ConnectionInterface $client, string $action, $data)
    {
        $client->send(
            Json::stringify([
                'ACTION' => $action,
                'DATA'   => $data
            ])
        );
    }
    
    /**
     * Send any type of data to anyone but the client
     */
    public static function sendMessageToEveryoneButClient(ConnectionInterface $client, string $action, $data)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$clients as $cli) {
            // skip this client
            if ($cli === $client) {
                continue;
            }
            
            $cli->send(
                Json::stringify([
                    'ACTION' => $action,
                    'DATA'   => $data
                ])
            );
        }
    }
    
    /**
     * Send any type of data to everyone
     */
    public static function sendMessageToEveryone(string $action, $data)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$clients as $cli) {
            $cli->send(
                Json::stringify([
                    'ACTION' => $action,
                    'DATA'   => $data
                ])
            );
        }
    }
}
