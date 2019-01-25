<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiUserBannedException extends HttpException
{
    const CODE    = 403;
    const MESSAGE = 'You have been banned from using the API. Please join the discord and talk to a moderator for more information.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
