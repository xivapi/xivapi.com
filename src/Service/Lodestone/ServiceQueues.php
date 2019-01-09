<?php

namespace App\Service\Lodestone;

class ServiceQueues
{
    const CACHE_CHARACTER_QUEUE             = 'lodestone_characters';
    const CACHE_ACHIEVEMENTS_QUEUE          = 'lodestone_achievements';
    const CACHE_FRIENDS_QUEUE               = 'lodestone_friends';
    const CACHE_FREECOMPANY_QUEUE           = 'lodestone_freecompany';
    const CACHE_FREECOMPANY_MEMBERS_QUEUE   = 'lodestone_freecompany_members';
    const CACHE_LINKSHELL_QUEUE             = 'lodestone_linkshell';
    const CACHE_PVPTEAM_QUEUE               = 'lodestone_pvpteam';
    
    // timeout for manual update
    const UPDATE_TIMEOUT = 86400;
    
    // maximum characters to process per minute
    const TOTAL_CHARACTER_UPDATES    = 70;  // Max 1 page
    const TOTAL_CHARACTER_FRIENDS    = 25;  // Max 4 pages
    const TOTAL_ACHIEVEMENT_UPDATES  = 10;  // Always 10 pages
    const TOTAL_FREE_COMPANY_UPDATES = 10;  // Max: 10 pages
    const TOTAL_PVP_TEAM_UPDATES     = 25;  // Usually only 1 page
    const TOTAL_LINKSHELL_UPDATES    = 25;  // Max 3 pages
}
