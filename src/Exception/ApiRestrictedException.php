<?php

namespace App\Exception;

use App\Common\Exceptions\ExceptionTrait;

class ApiRestrictedException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Could not find a valid key in the URL request. Please check the URL you are requesting against has the key field. It should look like this: ?something=ewf&key=your_key.';
}
