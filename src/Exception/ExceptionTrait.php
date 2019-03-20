<?php

namespace App\Exception;

trait ExceptionTrait
{
    public function __construct(int $code = null, string $message = null)
    {
        parent::__construct($code ?: self::CODE, $message ?: self::MESSAGE);
    }
}
