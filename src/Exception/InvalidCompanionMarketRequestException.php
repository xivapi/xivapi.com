<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCompanionMarketRequestException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'You must provide either a Data-Center (dc=X) name OR a valid Server name (servers=X,Y,Z).';
}
