<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;
use App\Service\Content\Tooltips;

class InvalidTooltipsColumnCountException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Column count exceeds limit. The maximum number of columns allowed are: '. Tooltips::MAX_COLUMNS;
}
