<?php

namespace App\Common\Constants;

class DiscordConstants
{
    const GUILD_ID                  = 474518001173921794;

    const ROOM_CHAT                 = 474519195963490305;
    const ROOM_PATREON              = 513681231716810763;
    const ROOM_FANSITES             = 474519354986332180;
    const ROOM_BOT_SPAM             = 531971978316349450;
    const ROOM_LODESTONE            = 474586876892676106;
    const ROOM_IMP_XIVAPI           = 534880659026739200;
    const ROOM_IMP_MOGBOARD         = 557164349714726932;
    const ROOM_DEV_SCHEDULE         = 562943014876610560;
    const ROOM_API_LIBRARIES        = 560421227622039571;
    const ROOM_GITHUB               = 474519301865340938;
    const ROOM_BETA                 = 477631558317244427;
    const ROOM_COMPANION_ERRORS     = 571007332616503296;
    const ROOM_MOG                  = 538316536688017418;
    const ROOM_ERRORS               = 569968196455759907;
    const ROOM_MB_FEEDBACK          = 574593645626523669;
    const ROOM_SER_AYMERIC          = 569968196455759907;

    // ROLES
    const ROLE_PATREON_HEALER       = 563452038982270989;
    const ROLE_PATREON_DPS          = 563455760869228544;
    const ROLE_PATREON_ADVENTURER   = 563446436176330752;
    const ROLE_PATREON_TANK         = 563453748433649674;
    const ROLE_PATREON_TIERS = [
        self::ROLE_PATREON_DPS        => 4,
        self::ROLE_PATREON_HEALER     => 3,
        self::ROLE_PATREON_TANK       => 2,
        self::ROLE_PATREON_ADVENTURER => 1,
    ];
}
