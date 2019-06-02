<?php

namespace App\WebSockets\BattleBar;

use Ratchet\ConnectionInterface;

/**
 * Handles all active clients
 */
class Clients
{
    /** @var array */
    private static $clients = [];
    /** @var array */
    private static $clientTypes = [];
    /** @var array */
    private static $clientToApiKey = [];
    /** @var array */
    private static $clientToRoom = [];
    /** @var array */
    private static $apiKeyToWebClient = [];
    /** @var array */
    private static $apiKeyToAppClient = [];

    public static function add(ConnectionInterface $client)
    {
        self::$clients[$client->resourceId] = $client;
    }

    public static function remove(ConnectionInterface $client)
    {
        // get the users api key
        $apikey = self::$clientToApiKey[$client->resourceId] ?? null;
    
        // remove client from list
        unset(self::$clients[$client->resourceId]);
    
        // remove client from any rooms
        unset(self::$clientToRoom[$client->resourceId]);
        
        // if api key is null, nothing else to do
        if ($apikey === null) {
            return;
        }

        // handle what happens based on type
        $type = self::$clientTypes[$client->resourceId];
    
        // get the clients
        $clients = self::getClientsViaApiKey($apikey);
        
        // if they leave the web page, end the client app connection
        if ($type === 'WEB') {
            // notify app of disconnect
            if ($clients->app) {
                self::sendMessageToClient($clients->app, 'WEB_DISCONNECTED');
            }
            
            // remove API key from both web and app
            unset(self::$apiKeyToWebClient[$apikey]);
            unset(self::$apiKeyToAppClient[$apikey]);
            
            // leave any battle rooms
            BattleRooms::leaveRoom($apikey);
        }
    
        // if they leave the app, we just need to notify the browser and remove key/client combo
        if ($type === 'APP') {
            // notify web id app close
            if ($clients->web) {
                self::sendMessageToClient($clients->web, 'APP_DISCONNECTED');
            }
            
            // remove API key from app
            unset(self::$apiKeyToAppClient[$apikey]);
        }
    }

    public static function get($resourceId)
    {
        return self::$clients[$resourceId];
    }

    public static function count()
    {
        return self::$clients->count();
    }
    
    /**
     * Register the web client
     */
    public static function registerWebClient(ConnectionInterface $client, $apiKey)
    {
        self::$clientTypes[$client->resourceId]    = 'WEB';
        self::$clientToApiKey[$client->resourceId] = $apiKey;
        self::$apiKeyToWebClient[$apiKey]          = $client->resourceId;
    }
    
    /**
     * Register the App client
     */
    public static function registerAppClient(ConnectionInterface $client, $apiKey)
    {
        self::$clientTypes[$client->resourceId]    = 'APP';
        self::$clientToApiKey[$client->resourceId] = $apiKey;
        self::$apiKeyToAppClient[$apiKey]          = $client->resourceId;
    }
    
    /**
     * Register a client to a specific remove
     */
    public static function registerClientToRoom(ConnectionInterface $client, BattleRoom $room)
    {
        self::$clientToRoom[$client->resourceId] = $room->id;
    }
    
    /**
     * This will return both the web and app client for an API key, this
     * provides a bridge between the browser and the app.
     */
    public static function getClientsViaApiKey($apiKey): \stdClass
    {
        $web = self::$apiKeyToWebClient[$apiKey] ?? null;
        $app = self::$apiKeyToAppClient[$apiKey] ?? null;
        
        if ($web) {
            $web = self::$clients[$web] ?? null;
        }
        
        if ($app) {
            $app = self::$clients[$app] ?? null;
        }
        
        return (Object)[
            'web' => $web,
            'app' => $app
        ];
    }
    
    /**
     * Send any type of data to the client
     */
    public static function sendMessageToClient(ConnectionInterface $client, string $action, $data = null)
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
    public static function sendMessageToEveryoneButClient(ConnectionInterface $client, string $action, $data = null)
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
    public static function sendMessageToEveryone(string $action, $data = null)
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
    
    /**
     * Send a message to all web clients
     */
    public static function sendMessageToAllWebClients(string $action, $data = null)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$apiKeyToWebClient as $clientId) {
            $cli = self::$clients[$clientId];
            $cli->send(
                Json::stringify([
                    'ACTION' => $action,
                    'DATA'   => $data
                ])
            );
        }
    }
    
    /**
     * Send a message to all app clients
     */
    public static function sendMessageToAllAppClients(string $action, $data = null)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$apiKeyToAppClient as $clientId) {
            $cli = self::$clients[$clientId];
            $cli->send(
                Json::stringify([
                    'ACTION' => $action,
                    'DATA'   => $data
                ])
            );
        }
    }
    
    public static function sendMessageToAllWebInRoom(BattleRoom $room, string $action, $data = null)
    {
        /** @var ConnectionInterface $cli */
        foreach (self::$apiKeyToWebClient as $clientId) {
            if (self::$clientToRoom[$clientId] !== $clientId) {
                continue;
            }
            
            $cli = self::$clients[$clientId];
            $cli->send(
                Json::stringify([
                    'ACTION' => $action,
                    'DATA'   => $data
                ])
            );
        }
    }
}
