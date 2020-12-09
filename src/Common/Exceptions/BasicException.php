<?php

namespace App\Common\Exceptions;

class BasicException extends \Exception
{
    use ExceptionTrait;

    const CODE    = 500;
    const MESSAGE = 'Basic Exception';
}
