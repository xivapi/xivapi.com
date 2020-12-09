<?php

namespace App\Common\Exceptions;

class JsonException extends \Exception
{
    use ExceptionTrait;

    const CODE    = 500;
    const MESSAGE = 'General Json Exception';
}
