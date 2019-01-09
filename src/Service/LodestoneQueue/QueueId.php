<?php

namespace App\Service\LodestoneQueue;

use Ramsey\Uuid\Uuid;

class QueueId
{
    private static $id = null;

    public static function get()
    {
        if (self::$id === null) {
            self::set();
        }
        
        return self::$id ?: 'none_set';
    }
    
    private static function set()
    {
        self::$id = substr(Uuid::uuid4()->toString(), 0, 8) . '.' . date('Y_m_d_H_i');
    }
}
