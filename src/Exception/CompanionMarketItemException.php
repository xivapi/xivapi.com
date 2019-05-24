<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class CompanionMarketItemException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 404;
    const MESSAGE = 'Could not find any item results for the specified item id.';
}
