<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiRestrictedException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'You do not have permission to use this API.';
}
