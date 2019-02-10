<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

trait ExceptionTrait
{
    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
