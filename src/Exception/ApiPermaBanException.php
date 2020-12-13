<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiPermaBanException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 403;
    const MESSAGE = 'Requests from this source have been permanently been banned due to excessive requests. Talk to staff in Discord.';
}
