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

    public static function add(ConnectionInterface $ci)
    {
        self::$clients->attach($ci);
    }

    public static function remove(ConnectionInterface $ci)
    {
        self::$clients->detach($ci);
    }

    public static function hash(ConnectionInterface $ci)
    {
        return self::$clients->getHash($ci);
    }

    public static function get($hash)
    {
        return self::$clients[$hash];
    }

    public static function count()
    {
        return self::$clients->count();
    }
}
