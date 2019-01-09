<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRateLimitException extends HttpException
{
    const CODE    = 429;
    const MESSAGE = 'App receiving too many requests from this IP';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
