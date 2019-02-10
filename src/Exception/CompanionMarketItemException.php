<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanionMarketItemException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 404;
    const MESSAGE = 'Could not find any item results for the specified item id.';
}
