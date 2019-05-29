<?php

namespace App\WebSockets\BattleBar;

class BattleRooms
{
    private static $rooms = [];
    
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
    public static function fetch(string $roomId)
    {
        $room = self::$rooms[$roomId] ?? null;
        return $room ? $room : null;
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
}
