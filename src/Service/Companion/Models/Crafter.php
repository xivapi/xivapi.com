<?php

namespace App\Service\Companion\Models;

use App\Common\Game\GameServers;
use App\Entity\CompanionCharacter;

/**
 * This is a JSON Model
 */
class Crafter
{
    public $ID;
    public $LodestoneID;
    public $Name;
    public $Server;
    public $Items = [];
    
    public static function build(CompanionCharacter $entity): self
    {
        $obj              = new Crafter();
        $obj->LodestoneID = $entity->getLodestoneId();
        $obj->ID          = $entity->getId();
        $obj->Name        = $entity->getName();
        $obj->Server      = GameServers::LIST[$entity->getServer()];
        return $obj;
    }
}
