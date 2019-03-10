<?php

namespace App\Service\User\SSO;

interface SignInInterface
{
    public function getName(): string;
    
    public function getLoginAuthorizationUrl(): SSOAuth;
    
    public function setLoginAuthorizationState(): SSOAccess;
    
    public function getAuthorizationToken(): SSOAccess;
    
    public function isCsrfValid(): bool;
}
