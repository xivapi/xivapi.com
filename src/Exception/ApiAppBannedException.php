<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiAppBannedException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 403;
    const MESSAGE = 'This API Key has been banned from the API. Please join the discord and talk to a moderator for more information.';
}
