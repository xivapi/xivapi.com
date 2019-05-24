<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class MaintenanceException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 503;
    const MESSAGE = 'You must be logged in to view this resource.';
}
