<?php

namespace App\Service\User\SSO;

use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Wohali\OAuth2\Client\Provider\Discord;

class DiscordSignIn implements SignInInterface
{
    const NAME            = 'discord';
    const STATE_KEY       = 'oauth2state';
    const CLIENT_RETURN   = '/app/login/discord/success';
    CONST CLIENT_OPTIONS  = [
        'scope' => ['identify','email']
    ];
    
    /** @var Discord */
    private $provider;
    /** @var Request */
    private $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->provider = new Discord([
            'clientId'      => getenv('DISCORD_CLIENT_ID'),
            'clientSecret'  => getenv('DISCORD_CLIENT_SECRET'),
            'redirectUri'   => $request->getScheme() .'://'. $request->getHost() . self::CLIENT_RETURN,
        ]);
    }
    
    /**
     * Get the access token
     */
    public function getAccessToken(AccessToken $token, $user): SSOAccess
    {
        $ssoAccess                  = new SSOAccess();
        $ssoAccess->name            = self::NAME;
        $ssoAccess->id              = $user->getId();
        $ssoAccess->username        = $user->getUsername();
        $ssoAccess->email           = $user->getEmail() ?: 'none';
        $ssoAccess->avatar          = $user->getAvatarHash();
        $ssoAccess->expires         = $token->getToken();
        $ssoAccess->tokenAccess     = $token->getToken();
        $ssoAccess->tokenRefresh    = $token->getRefreshToken();
        return $ssoAccess;
    }
    
    /**
     * Get login authorization url
     */
    public function getLoginAuthorizationUrl(): SSOAuth
    {
        $url = $this->provider->getAuthorizationUrl(self::CLIENT_OPTIONS);
        $this->request->getSession()->set(
            self::STATE_KEY,
            $this->provider->getState()
        );
        
        return new SSOAuth($url, $this->provider->getState());
    }
    
    /**
     * Set login authorization state
     */
    public function setLoginAuthorizationState(): SSOAccess
    {
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $this->request->get('code')
        ]);
        
        $this->request->getSession()->set('discord', $token->jsonSerialize());
        $user = $this->provider->getResourceOwner($token);
        
        return $this->getAccessToken($token, $user);
    }
    
    /**
     * Get authorization token
     */
    public function getAuthorizationToken(): SSOAccess
    {
        $token = $this->request->getSession()->get(self::NAME);
        
        // if expired, refresh the token
        if ($token['expires'] < time()) {
            return $this->refreshAuthorizationToken();
        }
        
        $token = new AccessToken($token);
        $user = $this->provider->getResourceOwner($token);
        return $this->getAccessToken($token, $user);
    }
    
    /**
     * Refresh the authorization token
     */
    public function refreshAuthorizationToken(): SSOAccess
    {
        $token = $this->request->getSession()->get(self::NAME);
        $token = $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $token->refresh_token
        ]);

        $user = $this->provider->getResourceOwner($token);
        return $this->getAccessToken($token, $user);
    }
    
    /**
     * CSRF validation protection
     */
    public function isCsrfValid(): bool
    {
        if (empty($this->request->get('state')) ||
            $this->request->get('state') !== $this->request->getSession()->get(self::STATE_KEY)
        ) {
            return false;
        }
        
        return true;
    }
}
