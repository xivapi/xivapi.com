<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class InvalidCompanionMarketRequestException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'You must provide either a Data-Center (dc=X) name OR a valid Server name (servers=X,Y,Z).';
}
