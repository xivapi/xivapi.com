<?php

namespace App\WebSockets\BattleBar;

class BattleRooms
{
    /** @var array */
    private static $rooms = [];
    /** @var array */
    private static $apiKeyInRoom = [];
    
    /**
     * get all battle rooms
     */
    public static function all()
    {
        return self::$rooms;
    }
    
    /**
     * Fetch a room via its id
     */
    public static function fetch(string $roomId): ?BattleRoom
    {
        $room = self::$rooms[$roomId] ?? null;
        return $room ? $room : null;
    }
    
    public static function update($apikey, BattleRoom $room)
    {
        self::$rooms[$room->id] = $room;
        self::$apiKeyInRoom[$apikey] = $room;
    }
    
    /**
     * Creates a new room and returns the new room id
     */
    public static function create($data)
    {
        $newRoom = new BattleRoom($data);
        self::$rooms[$newRoom->id] = $newRoom;
        return $newRoom->id;
    }
    
    /**
     * api key joins a battle room
     */
    public static function joinRoom($apikey, BattleRoom $room)
    {
        self::$apiKeyInRoom[$apikey] = $room;
    }
    
    /**
     * api key leaves a battle room
     */
    public static function leaveRoom($apikey)
    {
        unset(self::$apiKeyInRoom[$apikey]);
    }
    
    /**
     * get the room an api key is in
     */
    public static function getRoomViaApiKey($apikey)
    {
        return self::$apiKeyInRoom[$apikey] ?? null;
    }
}
