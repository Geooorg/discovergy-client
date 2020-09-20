<?php

include('helpers.php');
include('discovergyClient.php');

class OAuth1ClientBuilder
{
    const HOST_URL = "https://api.discovergy.com";
    const CREDENTIALS_FILE = 'credentials';
    var $consumerKey;
    var $consumerSecret;
    var $authorizeToken;
    var $authorizeTokenSecret;
    var $verifier;

    public function buildClient() {
        $this->registerClient()
            ->getRequestToken()
            ->getVerifier()
            ->getAccessToken();

        return new DiscovergyClient(self::HOST_URL, $this->consumerKey, $this->consumerSecret, $this->authorizeToken, $this->authorizeTokenSecret);
    }

    private function registerClient()
    {
        $path = "/public/v1/oauth1/consumer_token";
        $headers =
            'Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2' . "\r\n" .
            'Content-Type: application/x-www-form-urlencoded';

        $json = OAuthHelpers::executeHttpRequest(self::HOST_URL . $path, 'POST', $headers, array('client' => 'phpClient_v0.1'));

        $consumerDetails = json_decode($json);
        $this->consumerKey = $consumerDetails->key;
        $this->consumerSecret = $consumerDetails->secret;
        return $this;
    }

    private function getRequestToken()
    {
        $path = "/public/v1/oauth1/request_token";
        $url = self::HOST_URL . $path;

        $consumerKey = $this->consumerKey;
        $consumerSecret = $this->consumerSecret;
        $uuid = uniqid();
        $timestamp = time();
        $oauthParams = <<<TPL
oauth_callback="oob",oauth_consumer_key="${consumerKey}",oauth_nonce="${uuid}",oauth_signature_method="HMAC-SHA1",oauth_timestamp="${timestamp}",oauth_version="1.0"
TPL;
        $signature = OAuthHelpers::createSignatureFromParams($oauthParams, 'POST', $url, urlencode($consumerSecret) . '&');

        $oauthHeader = "Authorization: OAuth ${oauthParams},oauth_signature=\"${signature}\"";
        $response = OAuthHelpers::executeHttpRequest(self::HOST_URL . $path, 'POST', $oauthHeader, array());

        $this->authorizeToken = OAuthHelpers::extractOauthToken($response);
        $this->authorizeTokenSecret = OAuthHelpers::extractOauthTokenSecret($response);
        return $this;
    }

    private function getVerifier()
    {
        $oauthToken = $this->authorizeToken;
        $username = trim($this->readCredentials()[0]);
        $password = trim($this->readCredentials()[1]);
        $path = "/public/v1/oauth1/authorize?oauth_token=${oauthToken}&email=${username}&password=${password}";

        $acceptHeader = 'Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2' . "\r\n";
        $response = OAuthHelpers::executeHttpRequest(self::HOST_URL . $path, 'GET', $acceptHeader, array());

        $this->verifier = str_replace('oauth_verifier=', '', $response);
        return $this;
    }

    private function getAccessToken()
    {
        $path = "/public/v1/oauth1/access_token";
        $url = self::HOST_URL . $path;

        $consumerKey = $this->consumerKey;
        $oauthToken = $this->authorizeToken;
        $verifier = $this->verifier;
        $uuid = uniqid();
        $timestamp = time();
        $oauthParams = <<<TPL
oauth_consumer_key="${consumerKey}",oauth_nonce="${uuid}",oauth_signature_method="HMAC-SHA1",oauth_timestamp="${timestamp}",oauth_token="${oauthToken}",oauth_verifier="${verifier}",oauth_version="1.0"
TPL;
        $signature = OAuthHelpers::createSignatureFromParams($oauthParams, 'POST', $url, urlencode($this->consumerSecret) . '&' . $this->authorizeTokenSecret);
        $oauthHeader = "Authorization: OAuth ${oauthParams},oauth_signature=\"${signature}\"";
        $response = OAuthHelpers::executeHttpRequest(self::HOST_URL . $path, 'POST', $oauthHeader, array());

        $this->authorizeToken = OAuthHelpers::extractOauthToken($response);
        $this->authorizeTokenSecret = OAuthHelpers::extractOauthTokenSecret($response);
        return $this;
    }

    private function readCredentials()
    {
        return file(self::CREDENTIALS_FILE);
    }
}

?>

