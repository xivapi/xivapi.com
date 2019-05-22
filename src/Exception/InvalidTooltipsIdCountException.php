<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;
use App\Service\Content\Tooltips;

class InvalidTooltipsIdCountException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'ID Count exceeds limit. The maximum number of ids allowed are: '. Tooltips::MAX_IDS;
}
