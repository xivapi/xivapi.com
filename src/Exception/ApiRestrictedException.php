<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRestrictedException extends HttpException
{
    const CODE    = 400;
    const MESSAGE = 'Your API key does not have access to this endpoint.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
