<?php

namespace App\Common\Constants;

class RedisConstants
{
    const LOCAL             = 'REDIS_SERVER_LOCAL';
    const PROD              = 'REDIS_SERVER_PROD';
    const TIME_24_HOURS     = (60 * 60 * 24);
    const TIME_7_DAYS       = (60 * 60 * 24 * 7);
    const TIME_30_DAYS      = (60 * 60 * 24 * 30);
    const TIME_1_YEAR       = (60 * 60 * 24 * 365);
    const TIME_10_YEAR      = (60 * 60 * 24 * 365 * 10);
}
