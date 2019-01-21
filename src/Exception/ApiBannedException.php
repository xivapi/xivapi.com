<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiBannedException extends HttpException
{
    const CODE    = 403;
    const MESSAGE = 'You have been banned from using the API.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
