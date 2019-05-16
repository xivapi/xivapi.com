<?php

namespace App\Service\Companion\Models;

use App\Entity\CompanionCharacter;

/**
 * This is a JSON Model
 */
class Buyer
{
    public $ID;
    public $LodestoneID;
    public $Name;
    public $Server;
    public $Updated;
    public $Added;
    public $History = [];

    public static function build(CompanionCharacter $character): Buyer
    {
        $buyer              = new Buyer();
        $buyer->ID          = $character->getId();
        $buyer->LodestoneID = $character->getLodestoneId();
        $buyer->Name        = $character->getName();
        $buyer->Server      = $character->getServer();
        $buyer->Updated     = $character->getUpdated();
        $buyer->Added       = $character->getAdded();
        return $buyer;
    }

    public function addHistory($source)
    {
        $item = GameItem::build($source['ItemID']);

        // grab the prices
        foreach ($source['History'] as $price) {
            if ($price['CharacterID'] == $this->ID) {
                $marketListing = new MarketHistory();

                // these fields map 1:1
                foreach ($price as $key => $value) {
                    $marketListing->{$key} = $value;
                }

                $marketListing->Item = $item;
                $this->History[] = (array)$marketListing;
            }
        }
    }
}
