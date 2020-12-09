<?php

namespace App\Common\User;

use App\Common\Exceptions\CSRFInvalidationException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Wohali\OAuth2\Client\Provider\Discord;

class SignInDiscord implements SignInInterface
{
    const NAME            = 'discord';
    const STATE_KEY       = 'oauth2state';
    const CLIENT_RETURN   = '/account/login/discord/success';
    const CLIENT_SCOPE    = ['identify','email'];
    
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
     * get information from the SSO
     */
    public function getSsoAccess(AccessTokenInterface $token, $user): \stdClass
    {
        $obj                  = (Object)[];
        $obj->name            = self::NAME;
        $obj->id              = $user->getId();
        $obj->username        = $user->getUsername();
        $obj->email           = $user->getEmail() ?: 'none';
        $obj->avatar          = $user->getAvatarHash();
        $obj->tokenExpires    = $token->getExpires();
        $obj->tokenAccess     = $token->getToken();
        $obj->tokenRefresh    = $token->getRefreshToken();
        return $obj;
    }
    
    /**
     * Get login authorization url
     */
    public function getLoginAuthorizationUrl(): string
    {
        // generate an authorization url (this also generates the state)
        $url = $this->provider->getAuthorizationUrl([
            'scope' => self::CLIENT_SCOPE,
        ]);

        // set state in session for CSRF checking
        $this->request->getSession()->set('state', $this->provider->getState());
        return $url;
    }
    
    /**
     * Set login authorization state
     */
    public function setLoginAuthorizationState(): \stdClass
    {
        // check CSRF
        if ($this->request->get('state') !== $this->request->getSession()->get('state')) {
            throw new CSRFInvalidationException();
        }
        
        /** @var AccessToken $token */
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $this->request->get('code')
        ]);
        
        $this->request->getSession()->set('discord', $token->jsonSerialize());
        $user = $this->provider->getResourceOwner($token);
        
        return $this->getSsoAccess($token, $user);
    }
    
    /**
     * Get authorization token
     */
    public function getAuthorizationToken(): \stdClass
    {
        $token = $this->request->getSession()->get(self::NAME);
        
        // if expired, refresh the token
        if ($token['expires'] < time()) {
            return $this->refreshAuthorizationToken();
        }
        
        $token = new AccessToken($token);
        $user = $this->provider->getResourceOwner($token);
        return $this->getSsoAccess($token, $user);
    }
    
    /**
     * Refresh the authorization token
     */
    public function refreshAuthorizationToken(): \stdClass
    {
        $token = $this->request->getSession()->get(self::NAME);
        $token = $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $token->refresh_token
        ]);

        $user = $this->provider->getResourceOwner($token);
        return $this->getSsoAccess($token, $user);
    }
}
