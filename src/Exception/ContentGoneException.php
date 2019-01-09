<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentGoneException extends HttpException
{
    const CODE = 410;
}
