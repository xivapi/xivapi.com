<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class InvalidSearchRequestException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid search request, missing body param in json payload.';
}
