<?php

namespace esuite\MIMBundle\Service;

use esuite\MIMBundle\Service\Oauth\OauthClient;

class edotAuthenticator
{
    protected $oauthClient;

    public function __construct(OauthClient $oauthClient)
    {
        $this->oauthClient = $oauthClient;
    }

    /**
     * Function to authenticate and get access_token & refresh_token
     * @param $username
     * @param $password
     *
     * @return array() that contains access_token, refresh_token, token_type & expires_in
     */
    public function authenticate($username, $password)
    {
        // Add params initialized (grant_type & scope)
        $data = ['username' => $username, 'password' => $password, 'grant_type' => 'password', 'scope' => 'scope-mim-readwrite'];

        $response  = $this->oauthClient->sendRequest($data);
        return $response;
    }

    /**
     * Function to get new access_token using refresh_token
     * @param $refreshToken
     *
     * @return string that contains access_token
     */
    public function refreshAccessToken($refreshToken)
    {
        // Add params initialized (grant_type & scope)
        $data = ['refresh_token' => $refreshToken, 'grant_type' => 'refresh_token'];

        $response  = $this->oauthClient->sendRequest($data);
        return $response;
    }

}
