<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRateLimitException extends HttpException
{
    const CODE    = 429;
    const MESSAGE = 'App receiving too many requests from this IP: %s / %s';

    public function __construct(int $count, int $limit)
    {
        parent::__construct(self::CODE, sprintf(
            self::MESSAGE,
            $count,
            $limit
        ));
    }
}
