<?php

if (!$_GET['code']) {
    dump('Let Shoptet call this script');
    die();
}

$SHOP_SUBDOMAIN = getenv('SHOP_SUBDOMAIN'); // https://${SHOP_SUBDOMAIN}.myshoptet.com
$CLIENT_ID = getenv('CLIENT_ID');
$REDIRECT_URL = getenv('REDIRECT_URL')
    ? getenv('REDIRECT_URL')
    : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];


$data = [
    'code' => $_GET['code'],
    'grant_type' => 'authorization_code',
    'client_id' => $CLIENT_ID,
    'redirect_uri' => $REDIRECT_URL,
    'scope' => 'api',
];

$ch = curl_init("https://$SHOP_SUBDOMAIN.myshoptet.com/action/ApiOAuthServer/token");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (!$response) {
    dump('Curl error: ' . curl_error($ch));
}

curl_close($ch);

if (!$response) {
    die();
}

$response = json_decode($response, true);
$oauthAccessToken = $response['access_token'];

if ($oauthAccessToken) {
    dump('OAuth access token: ' . $oauthAccessToken);
} else {
    dump("Could not find OAuth access token in response, it was:\n");
    dump(print_r($response, true));
}

// Sends both as a response and to a server log
function dump($data) {
    error_log($data);
    echo $data;
}
