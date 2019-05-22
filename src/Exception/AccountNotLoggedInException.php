<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class AccountNotLoggedInException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 401;
    const MESSAGE = 'You must be logged in to view this resource.';
}
