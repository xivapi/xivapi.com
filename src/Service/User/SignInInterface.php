<?php

namespace App\Service\User;

interface SignInInterface
{
    public function getLoginAuthorizationUrl(): string;

    public function setLoginAuthorizationState(): \stdClass;

    public function getAuthorizationToken(): \stdClass;
}
