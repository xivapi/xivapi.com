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
    
    public static function sendMessageToClient(ConnectionInterface $client, string $message)
    {
        $client->send($message);
    }
    
    public static function sendMessageToEveryoneButClient(ConnectionInterface $client, string $message)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$clients as $cli) {
            // skip this client
            if ($cli === $client) {
                continue;
            }
            
            $cli->send($message);
        }
    }
    
    public static function sendMessageToEveryone(string $message)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$clients as $cli) {
            $cli->send($message);
        }
    }
}
