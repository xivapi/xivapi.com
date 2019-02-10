<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentGoneException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 410;
    const MESSAGE = 'It gone!';
}
