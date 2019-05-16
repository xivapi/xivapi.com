<?php

namespace App\Service\Companion\Models;

use App\Common\Game\GameServers;
use App\Entity\CompanionRetainer;

/**
 * This is a JSON Model
 */
class Retainer
{
    public $ID;
    public $Name;
    public $Server;
    public $Items = [];
    
    public static function build(CompanionRetainer $entity): self
    {
        $obj         = new Retainer();
        $obj->ID     = $entity->getId();
        $obj->Name   = $entity->getName();
        $obj->Server = GameServers::LIST[$entity->getServer()];
        return $obj;
    }
}
