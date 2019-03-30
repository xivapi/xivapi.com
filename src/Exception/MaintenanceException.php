<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 503;
    const MESSAGE = 'You must be logged in to view this resource.';
}
