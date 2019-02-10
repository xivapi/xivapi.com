<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountNotLoggedInException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 401;
    const MESSAGE = 'You must be logged in to view this resource.';
}
