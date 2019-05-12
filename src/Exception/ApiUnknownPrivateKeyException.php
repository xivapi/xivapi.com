<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiUnknownPrivateKeyException extends HttpException
{
    use ExceptionTrait;

    const CODE    = 401;
    const MESSAGE = 'Could not find a user for this key, please check your key or remove it.';
}
