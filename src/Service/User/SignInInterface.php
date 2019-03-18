<?php

namespace App\Service\User;

use App\Service\User\SSO\SSOAccess;

interface SignInInterface
{
    public function getLoginAuthorizationUrl(): string;
    
    public function setLoginAuthorizationState(): SSOAccess;
    
    public function getAuthorizationToken(): SSOAccess;
}
