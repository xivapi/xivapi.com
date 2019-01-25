<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountNotLoggedInException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, "You must be logged in to view this resource.");
    }
}
