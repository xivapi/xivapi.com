<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class LodestoneResponseErrorException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 200;
    const MESSAGE = 'Lodestone returned some error codes (this is not XIVAPI): %s';
}
