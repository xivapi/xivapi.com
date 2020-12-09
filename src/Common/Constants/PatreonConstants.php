<?php

namespace App\Common\Constants;

class PatreonConstants
{
    const NORMAL_USER            = 0;
    const PATREON_ADVENTURER     = 1;
    const PATREON_TANK           = 2;
    const PATREON_HEALER         = 3;
    const PATREON_DPS            = 4;
    const PATREON_BENEFIT        = 9;
    
    const PATREON_TIERS          = [
        self::NORMAL_USER        => 'Normal User',
        self::PATREON_ADVENTURER => 'Adventurer',
        self::PATREON_TANK       => 'Tank',
        self::PATREON_HEALER     => 'Healer',
        self::PATREON_DPS        => 'DPS',
        self::PATREON_BENEFIT    => 'Friendly Benefits',
    ];

    const FRIEND_BENEFIT_MAX = [
        self::PATREON_ADVENTURER => 0,
        self::PATREON_TANK       => 0,
        self::PATREON_HEALER     => 10,
        self::PATREON_DPS        => 50,
        self::PATREON_BENEFIT    => 0,
    ];

    const ALERT_DEFAULTS = [
        'MAX'                   => 5,
        'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 3),
        'UPDATE_TIMEOUT'        => false,
    ];
    
    const ALERT_PATRON = [
        'MAX'                   => 10,
        'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 14),
        'UPDATE_TIMEOUT'        => false,
    ];
    
    const ALERT_PATRON_DPS = [
        'MAX'                   => 20,
        'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 30),
        'UPDATE_TIMEOUT'        => true,
    ];
    
    const ALERT_LIMITS = [
        self::NORMAL_USER        => self::ALERT_DEFAULTS,
        self::PATREON_BENEFIT    => self::ALERT_PATRON,
        self::PATREON_ADVENTURER => self::ALERT_PATRON,
        self::PATREON_TANK       => self::ALERT_PATRON,
        self::PATREON_HEALER     => self::ALERT_PATRON,
        self::PATREON_DPS        => self::ALERT_PATRON_DPS,
    ];
}
