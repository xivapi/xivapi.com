<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class InvalidCompanionMarketRequestServerSizeException extends \Exception
{
    use ExceptionTrait;
    
    const CODE        = 400;
    const MESSAGE     = 'You have exceeded max number of servers: '. self::MAX_SERVERS;
    const MAX_SERVERS = 15;
}
