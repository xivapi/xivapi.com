<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedAccessException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, "You do not have permissions to access this endpoint.");
    }
}
