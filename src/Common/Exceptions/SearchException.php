<?php

namespace App\Common\Exceptions;

class SearchException extends \Exception
{
    use ExceptionTrait;

    const CODE    = 500;
    const MESSAGE = 'Search Exception';
}
