<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiUnauthorizedAccessException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 401;
    const MESSAGE = 'You do not have permissions to access this endpoint.';
}
