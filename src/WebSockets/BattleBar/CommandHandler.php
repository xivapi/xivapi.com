<?php

namespace App\WebSockets\BattleBar;

use Ratchet\ConnectionInterface;

/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 */
class CommandHandler
{
    public static function handle(ConnectionInterface $clientFrom, string $message)
    {
        [$source, $action, $data] = self::getActionFromMessage($message);

        if ($source == 'APP') {
            self::handleAppMessages($clientFrom, $action, $data);
            return;
        }
        
        self::handleWebMessages($clientFrom, $action, $data);
    }
    
    /**
     * Handle messages from the app
     */
    private static function handleAppMessages(ConnectionInterface $clientFrom, string $action, string $data)
    {
        switch ($action) {
            default:
                throw new \Exception("Unknown Action: {$action}");
        
            case 'PLAYER_NAME':
                Clients::sendMessageToEveryoneButClient($clientFrom, "PLAYER_NAME::{$data}");
                break;
                
            case 'PLAYER_DATA':
                Clients::sendMessageToEveryoneButClient($clientFrom, "PLAYER_DATA::{$data}");
                break;
    
            case 'TARGET_DATA':
                Clients::sendMessageToEveryoneButClient($clientFrom, "TARGET_DATA::{$data}");
                break;
        }
    }
    
    /**
     * Handle messages from the web
     */
    private static function handleWebMessages(ConnectionInterface $clientFrom, string $action, string $data)
    {
        switch ($action) {
            default:
                throw new \Exception("Unknown Action: {$action}");
            
            // todo - write cases for handling the action
        }
    }

    /**
     * Extract the source, action and data from a message
     */
    private static function getActionFromMessage(string $message)
    {
        $command = explode('::', $message, 3);

        $source  = $command[0] ?? null;
        $action  = $command[1] ?? null;
        $data    = $command[2] ?? null;

        if ($source == null || $action == null || $data == null) {
            throw new \Exception("Invalid action or data");
        }

        return [
            $source,
            $action,
            $data
        ];
    }
}
