<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiUserBannedException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 403;
    const MESSAGE = 'You have been banned from using the API. Please join the discord and talk to a moderator for more information.';
}
