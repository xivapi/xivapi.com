<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceException extends HttpException
{
    const CODE = 503;
}
