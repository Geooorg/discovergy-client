<?php

class OAuthHelpers
{
    public static function executeHttpRequest($url, $methodVerb, $headerString, $content)
    {
        $options = array(
            'http' => array(
                'header' => $headerString,
                'method' => $methodVerb,
                'content' => http_build_query($content)
            )
        );
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === FALSE) {
            print "An error occured!";
        }

        return $response;
    }

    public static function createSignatureFromParams($paramsStr, $verb, $url, $key)
    {
        $paramsWithCommaReplaced = str_replace('"', "", str_replace(",", "&", $paramsStr));
        $baseStr = "${verb}&" . urlencode($url) . "&" . urlencode($paramsWithCommaReplaced);

        return urlencode(base64_encode(hash_hmac('sha1', $baseStr, $key, true)));
    }

    public static function extractOauthToken($responseStr)
    {
        $pairs = explode('&', $responseStr);
        return str_replace('oauth_token=', '', $pairs[0]);
    }

    public static function extractOauthTokenSecret($responseStr)
    {
        $pairs = explode('&', $responseStr);
        return str_replace('oauth_token_secret=', '', $pairs[1]);
    }
}

?>