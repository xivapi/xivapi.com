<?php

namespace App\WebSockets\BattleBar;

use Github\Client;
use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 */
class CommandHandler
{
    public static function handle(ConnectionInterface $from, $request)
    {
        (new ConsoleOutput())->writeln($request->ACTION);
        
        switch ($request->ACTION) {
            default:
                throw new \Exception("Unknown Action: {$request->ACTION}");
               
            // register client
            case 'REGISTER_WEB_CLIENT':
                Clients::registerWebClient($from, $request->APIKEY);
                break;
    
            // register app
            case 'REGISTER_APP_CLIENT':
                // get clients from this key
                $clients = Clients::getClientsViaApiKey($request->APIKEY);
                
                if ($clients->web === null) {
                    // 30 = not on the web version
                    Clients::sendMessageToClient($from, 'REGISTER_APP_CLIENT', 30);
                    return;
                }
                
                Clients::registerAppClient($from, $request->APIKEY);
                Clients::sendMessageToClient($from, 'REGISTER_APP_CLIENT', 1);
                Clients::sendMessageToClient($clients->web, 'REGISTER_APP_CLIENT', 1);
                
                // if the web client is in a room, inform app
                if ($room = BattleRooms::getRoomViaApiKey($request->APIKEY)) {
                    Clients::sendMessageToClient($from, 'LOAD_ROOM', $room);
                }
                break;
    
            // create game rooms
            case 'CREATE_ROOM':
                // create the room and tell the client to join it
                Clients::sendMessageToClient($from, 'JOIN_ROOM', BattleRooms::create($request->DATA));
            
                // send the list of rooms back to the client
                Clients::sendMessageToClient($from, 'LIST_ROOMS', BattleRooms::all());
                break;
        
            // list game rooms
            case 'LIST_ROOMS':
                Clients::sendMessageToClient($from, 'LIST_ROOMS', BattleRooms::all());
                break;
        
            // join a game room
            case 'JOIN_ROOM':
                // get clients from this key
                $clients = Clients::getClientsViaApiKey($request->APIKEY);
                
                // fetch room
                $room = BattleRooms::fetch($request->DATA);
                
                // give the web client the room info
                Clients::sendMessageToClient($clients->web, 'LOAD_ROOM', $room);
                
                // track the join
                BattleRooms::joinRoom($request->APIKEY, $room);
                
                // if web app open, perform actions
                if ($clients->web) {
                    // register client to room
                    Clients::registerClientToRoom($clients->web, $room);
                }
                
                // if client open, perform actions
                if ($clients->app) {
                    // give the app client the room info
                    Clients::sendMessageToClient($clients->app, 'LOAD_ROOM', BattleRooms::fetch($request->DATA));
    
                    // register client to room
                    Clients::registerClientToRoom($clients->app, $room);
                }
                break;
                
            #------------------------------------------------------------------------------------------
    
            // send player name to the browser
            case 'GAME_PLAYER_NAME':
                $clients = Clients::getClientsViaApiKey($request->APIKEY);
                Clients::sendMessageToClient($clients->web, "GAME_PLAYER_NAME", $request->DATA);
                break;
    
            // send player data to the browser
            case 'GAME_PLAYER_DATA':
                $clients = Clients::getClientsViaApiKey($request->APIKEY);
                Clients::sendMessageToClient($clients->web, "GAME_PLAYER_DATA", $request->DATA);
                break;
    
            // game mob data
            case 'GAME_MOB_DATA':
                $clients = Clients::getClientsViaApiKey($request->APIKEY);
                
                // get the room the player is in
                /** @var BattleRoom $room */
                $room = BattleRooms::getRoomViaApiKey($request->APIKEY);
                
                // don't seem to be in a battle room???
                if ($room === null) {
                    return;
                }
                
                // grab monster data
                $monsterData = $request->DATA->MonstersData;
                
                if (empty($monsterData)) {
                    return;
                }
                
                // grab old monster list
                $oldMonsterSpawnIds = array_keys($room->monstersData);
                $newMonsterSpawnIds = [];
            
                // populate monster ids
                foreach ($monsterData as $mob) {
                    $newMonsterSpawnIds[] = $mob->spawn_id;
                    $room->monstersData[$mob->spawn_id] = $mob;
                }
                
                // look for any monsters
                $removedMonsterIds = array_diff($oldMonsterSpawnIds, $newMonsterSpawnIds);
                
                // if we have any removed ids, inform client to remove from list
                if ($removedMonsterIds) {
                    self::removeMonstersFromRoom($request->APIKEY, $removedMonsterIds);
                    Clients::sendMessageToAllWebInRoom($room, "GAME_MOB_REMOVE_SPAWNS", $removedMonsterIds);
                }
                
                // set party members for this room
                $room->party = $request->DATA->PartyMembers;
                
                // update room
                BattleRooms::update($request->APIKEY, $room);
                
                // send mob data to browser
                Clients::sendMessageToAllWebInRoom($clients->web, "GAME_MOB_DATA", $room);
                break;
                
            case 'GAME_MOB_DESPAWN':
                // get the room the player is in
                /** @var BattleRoom $room */
                $room = BattleRooms::getRoomViaApiKey($request->APIKEY);
                
                // remove monsters from room
                self::removeMonstersFromRoom($request->APIKEY, $request->DATA);
                
                // send to everyone
                Clients::sendMessageToAllWebInRoom($room, 'GAME_MOB_REMOVE_SPAWNS', $request->DATA);
                break;
                
        }
    }
    
    /**
     * This removes dead monsters from a game room
     */
    private static function removeMonstersFromRoom($apiKey, $monsters)
    {
        // get the room the player is in
        /** @var BattleRoom $room */
        $room = BattleRooms::getRoomViaApiKey($apiKey);
    
        // remove enemies from the room this client is in
        foreach ($monsters as $monsterId) {
            unset($room->monstersData[$monsterId]);
        }
    
        // update room
        BattleRooms::update($apiKey, $room);
    }
}
