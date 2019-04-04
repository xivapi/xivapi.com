<?php

namespace App\Service\ThirdParty\Patreon;

use Patreon\API;
use Patreon\OAuth;
use Symfony\Component\HttpFoundation\Request;

class Patreon
{
    const PATREON_URL_LOGIN   = '/account/patreon/login';
    const PATREON_URL_SUCCESS = '/account/patreon/success';
    const PATREON_OAUTH_URI   = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=%s&redirect_uri=%s';
    const PATREON_SCOPE_VARS  = [];

    public function generateLoginUri(Request $request)
    {
        $redirectUri = $request->getScheme() .'://'. $request->getHost() . self::PATREON_URL_LOGIN;

        $oAuthUrl = $this->getOAuthUrl($redirectUri);

        $state = self::PATREON_SCOPE_VARS;
        $state['final_page'] = self::PATREON_URL_SUCCESS;

        $state_parameters = '&state='. urlencode(base64_encode(json_encode($state)));

        $oAuthUrl .= $state_parameters;

        $scope_parameters = '&scope=identity%20identity'.urlencode('[email]');

        $oAuthUrl .= $scope_parameters;

        return $oAuthUrl;
    }

    public function handlePatreonOAuthCode(Request $request)
    {
        if ( $request->get('code') != '' ) {

            $oauth_client = new OAuth(getenv('PATREON_CLIENT_ID'), getenv('PATREON_SECRET_ID'));

            $redirectUri    = $request->getScheme() .'://'. $request->getHost() . self::PATREON_URL_LOGIN;
            $tokens         = $oauth_client->get_tokens($_GET['code'], $redirectUri);

            $access_token   = $tokens['access_token'];
            $refresh_token  = $tokens['refresh_token'];

            $api_client = new API($access_token);

            $api_client->api_return_format = 'object';

            $patron_response = $api_client->fetch_user();

            print_r($patron_response);


            die;
        }
    }

    private function getOAuthUrl($redirectUri)
    {
        return sprintf(
            self::PATREON_OAUTH_URI,
            getenv('PATREON_CLIENT_ID'),
            urlencode($redirectUri)
        );
    }
}
