<?php

namespace App\Exception;

use App\Service\Content\Tooltips;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidTooltipsColumnCountException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Column count exceeds limit. The maximum number of columns allowed are: '. Tooltips::MAX_COLUMNS;
}
