<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanionMarketServerException extends HttpException
{
    const CODE    = 400;
    const MESSAGE = 'Invalid server provided for request.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
