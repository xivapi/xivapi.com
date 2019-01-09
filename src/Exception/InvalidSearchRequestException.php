<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSearchRequestException extends HttpException
{
    const CODE    = 400;
    const MESSAGE = 'Invalid search request, missing body param in json payload.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
