<?php

namespace App\Common\Exceptions;

class ApiUnknownPrivateKeyException extends \Exception
{
    use ExceptionTrait;

    const CODE    = 401;
    const MESSAGE = 'Could not find a user for this key, please check your key or remove it.';
}
