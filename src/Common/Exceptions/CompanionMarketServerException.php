<?php

namespace App\Common\Exceptions;

class CompanionMarketServerException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid server provided for request. For individual Servers use: servers=1,2,3 for Data-Center use: dc=X';
}
