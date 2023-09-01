<?php

$username = '';
$password = '';
$meterId = '';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$arrContextOptions=array(
      "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    );

$tagesbeginn = strtotime('today')*1000-1000*60*60;
$tagesende = $tagesbeginn+1000*24*60*60;
$from=time();

$response = file_get_contents("https://".$username.":".$password."@api.discovergy.com/public/v1/readings?meterId=".$meterId."&resolution=three_minutes&from=".$tagesbeginn."&to=".$tagesende."&fields=power", false, stream_context_create($arrContextOptions));

//echo $response;

$decoded = json_decode($response, false);
$first = array_pop($decoded);

echo $first->values->power;

?>
