<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCompanionMarketRequestServerSizeException extends HttpException
{
    use ExceptionTrait;
    
    const CODE        = 400;
    const MESSAGE     = 'You have exceeded max number of servers: '. self::MAX_SERVERS;
    const MAX_SERVERS = 15;
}
