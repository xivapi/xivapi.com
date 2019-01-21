<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiSuspendedException extends HttpException
{
    const CODE    = 403;
    const MESSAGE = 'This API Key has been suspended from the API.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
