<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiTempBanException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 403;
    const MESSAGE = 'Requests from this source have been temporarily been banned due to excessive requests. Try again in an hour or talk to staff in Discord.';
}
