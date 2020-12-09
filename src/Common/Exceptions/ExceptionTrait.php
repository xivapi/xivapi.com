<?php

namespace App\Common\Exceptions;

trait ExceptionTrait
{
    public function __construct($message = null, $code = null)
    {
        parent::__construct($message ?: self::MESSAGE, $code ?: self::CODE);
    }
}
