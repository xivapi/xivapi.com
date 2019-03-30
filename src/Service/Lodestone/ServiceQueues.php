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
    const TOTAL_CHARACTER_UPDATES    = 75;  // Max 1 page
    const TOTAL_CHARACTER_FRIENDS    = 40;  // Max 4 pages
    const TOTAL_ACHIEVEMENT_UPDATES  = 2;  // Always 10 pages
    const TOTAL_FREE_COMPANY_UPDATES = 20;  // Max: 10 pages
    const TOTAL_PVP_TEAM_UPDATES     = 50;  // Usually only 1 page
    const TOTAL_LINKSHELL_UPDATES    = 50;  // Max 3 pages
    
    // queue counts (including 0
    const TOTAL_CHARACTER_QUEUES = 5;
    const TOTAL_CHARACTER_FRIENDS_QUEUES = 1;
    const TOTAL_CHARACTER_ACHIEVEMENTS_QUEUES = 3;
    const TOTAL_FC_QUEUES = 1;
    const TOTAL_LS_QUEUES = 1;
    const TOTAL_PVP_QUEUES = 1;
    
    const TOTAL_QUEUES_PATRON = 1;
}
