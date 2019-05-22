<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ContentGoneException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 410;
    const MESSAGE = 'It gone!';
}
