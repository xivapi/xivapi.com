<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiUnauthorizedAccessException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 401;
    const MESSAGE = 'You do not have permissions to access this endpoint.';
}
