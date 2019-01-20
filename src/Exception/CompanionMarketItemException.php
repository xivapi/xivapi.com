<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanionMarketItemException extends HttpException
{
    const CODE    = 404;
    const MESSAGE = 'Could not find any item results for the specified item id.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
