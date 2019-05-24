<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class InvalidTooltipsRequestException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid JSON payload. Tooltips require a JSON payload in the request body.';
}
