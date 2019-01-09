<?php

namespace App\Service\User\SSO;

class CsrfInvalidException extends \Exception
{
    const ERROR = 'Could not confirm the CSRF token from SSO Provider. Please try again.';
}
