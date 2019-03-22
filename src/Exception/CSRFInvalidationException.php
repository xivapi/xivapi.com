<?php

namespace App\Exception;

class CSRFInvalidationException extends \Exception
{
    const CODE    = 400;
    const MESSAGE = 'Could not confirm the CSRF token from SSO Provider. Please try again.';
}
