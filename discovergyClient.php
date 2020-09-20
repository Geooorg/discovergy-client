<?php

class DiscovergyClient {
    var $hostUrl;
    var $consumerKey;
    var $consumerSecret;
    var $authorizeToken;
    var $authorizeTokenSecret;

    function __construct($hostUrl, $consumerKey, $consumerSecret, $authorizeToken, $authorizeTokenSecret)
    {
        $this->hostUrl = $hostUrl;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->authorizeToken = $authorizeToken;
        $this->authorizeTokenSecret = $authorizeTokenSecret;
    }

    // METADATA
    // =======================
    public function getDevices($meterId)
    {
        $path = "/public/v1/devices";
        $queryParams = array('meterId' => $meterId);
        return $this->doGet($path, $queryParams);
    }

    public function getMeters()
    {
        $path = "/public/v1/meters";
        return $this->doGet($path, array(), false);
    }

    public function getFieldNames($meterId)
    {
        $path = "/public/v1/field_names";
        $queryParams = array('meterId' => $meterId);
        return $this->doGet($path, $queryParams); // => fieldNames: ["energy","energy1","energy2","energyOut","energyOut1","energyOut2","power"]
    }

    // MEASUREMENTS
    // =============================

    public function getReadings($meterId, $fields, $from, $to, $resolution = 'three_minutes')
    {
        $path = "/public/v1/readings";
        $queryParams = array('meterId' => $meterId, 'fields' => $fields, 'from' => $from, 'to' => $to, 'resolution' => $resolution);
        return $this->doGet($path, $queryParams);
    }

    public function getLastReading($meterId, $fields, $disaggregation = 'false', $each = 'false')
    {
        $path = "/public/v1/last_reading";
        $queryParams = array('meterId' => $meterId, 'fields' => $fields, 'disaggregation' => $disaggregation, 'each' => $each);
        return $this->doGet($path, $queryParams);
    }

    public function getStatistics($meterId, $fields, $from, $to)
    {
        $path = "/public/v1/statistics";
        $queryParams = array('meterId' => $meterId, 'fields' => $fields, 'from' => $from, 'to' => $to);
        return $this->doGet($path, $queryParams);
    }

    private function doGet($path, $queryParams, $asJson = true) {

        $consumerKey = $this->consumerKey;
        $oauthToken = $this->authorizeToken;
        $uuid = uniqid();
        $timestamp = time();
        $oauthParams = array(
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => $uuid,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $oauthToken,
            'oauth_version'=> '1.0'
        );

        $oauthParamsForSignature = $this->createOauthParamsForSignature($oauthParams, $queryParams);

        $key = urlencode($this->consumerSecret) . '&' . $this->authorizeTokenSecret;
        $url = $this->hostUrl . $path;

        $signature = OAuthHelpers::createSignatureFromParams($oauthParamsForSignature, 'GET', $url, $key);
        $oauthParamsForHeader = $this->asCommaDelimitedString($oauthParams);
        $oauthHeader = "Authorization: OAuth ${oauthParamsForHeader},oauth_signature=\"${signature}\"";

        $url = $this->appendQueryParams($url, $queryParams);
        $responseBody = OAuthHelpers::executeHttpRequest($url, 'GET', $oauthHeader, array());
        return $asJson ? $responseBody : json_decode($responseBody);
    }

    private function createOauthParamsForSignature($oauthParams, $queryParams)
    {
        // insert queryParameters into the oauthParams array and sort by key
        $mergedArray = array_merge($oauthParams, $queryParams);
        ksort($mergedArray);

        return $this->asCommaDelimitedString($mergedArray);
    }

    private function asCommaDelimitedString($oauthParams)
    {
        $params = array();
        foreach ($oauthParams as $key => $value) {
            $params[] = "$key=\"$value\"";
        }
        return implode(',', $params);
    }

    private function appendQueryParams($url, $queryParams)
    {
        $params = array();
        foreach ($queryParams as $key => $value) {
            $urlEncodedValue = urlencode($value);
            $params[] = "$key=$urlEncodedValue";
        }

        return $url.'?'.(implode("&", $params));
    }

}
