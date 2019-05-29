<?php

namespace App\WebSockets\BattleBar;

use Ratchet\ConnectionInterface;

/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 */
class CommandHandler
{
    public static function handle(ConnectionInterface $from, $request)
    {
        switch ($request->ACTION) {
            default:
                throw new \Exception("Unknown Action: {$request->ACTION}");
                
            case 'REGISTER_WEB_CLIENT':
                Clients::registerWebClient($from, $request->APIKEY);
                break;
    
            case 'REGISTER_APP_CLIENT':
                Clients::registerAppClient($from, $request->APIKEY);
                break;
        
            case 'CREATE_ROOM':
                // create the room and tell the client to join it
                Clients::sendMessageToClient($from, 'JOIN_ROOM', BattleRooms::create($request->DATA));
            
                // send the list of rooms back to the client
                Clients::sendMessageToClient($from, 'LIST_ROOMS', BattleRooms::all());
                break;
        
            case 'LIST_ROOMS':
                Clients::sendMessageToClient($from, 'LIST_ROOMS', BattleRooms::all());
                break;
        
            case 'JOIN_ROOM':
                Clients::sendMessageToClient($from, 'LOAD_ROOM', BattleRooms::fetch($request->DATA));
                break;
    
            case 'PLAYER_NAME':
                // todo - get the correct web client
                Clients::sendMessageToEveryoneButClient($from, "PLAYER_NAME", $request->DATA);
                break;
    
            case 'PLAYER_DATA':
                Clients::sendMessageToEveryoneButClient($from, "PLAYER_DATA", $request->DATA);
                break;
    
            case 'TARGET_DATA':
                Clients::sendMessageToEveryoneButClient($from, "TARGET_DATA", $request->DATA);
                break;
        }
    }
}
