<?php

namespace App\WebSockets\BattleBar;

use Ramsey\Uuid\Uuid;

class BattleRoom
{
    public $id;
    public $number;
    public $name;
    public $enemies;
    public $players = [];
    
    public function __construct(\stdClass $data)
    {
        $this->id      = Uuid::uuid4()->toString();
        $this->number  = mt_rand(111111,999999);
        $this->name    = $data->name;
        $this->enemies = $data->enemies;
    }
}
