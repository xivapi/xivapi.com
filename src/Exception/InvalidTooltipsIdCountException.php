<?php

namespace App\Exception;

use App\Service\Content\Tooltips;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidTooltipsIdCountException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'ID Count exceeds limit. The maximum number of ids allowed are: '. Tooltips::MAX_IDS;
}
