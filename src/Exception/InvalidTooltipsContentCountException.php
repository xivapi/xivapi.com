<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;
use App\Service\Content\Tooltips;

class InvalidTooltipsContentCountException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Content count exceeds limit. The maximum number of content fields allowed are: '. Tooltips::MAX_CONTENT;
}
