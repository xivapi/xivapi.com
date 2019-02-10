<?php

namespace App\Exception;

use App\Service\Content\Tooltips;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidTooltipsContentCountException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Content count exceeds limit. The maximum number of content fields allowed are: '. Tooltips::MAX_CONTENT;
}
