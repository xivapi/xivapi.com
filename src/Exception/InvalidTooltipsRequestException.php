<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidTooltipsRequestException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid JSON payload. Tooltips require a JSON payload in the request body.';
}
