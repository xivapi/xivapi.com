<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiRateLimitException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 429;
    const MESSAGE = 'App receiving too many requests from this IP.';
}
