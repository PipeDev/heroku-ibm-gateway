<?php

use GuzzleHttp\Client;

require 'vendor/autoload.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: content-type,x-Auth-APPID,x-Auth-TOKEN");
    }
    exit(0);
}

if ($_SERVER["HTTP_X_AUTH_TOKEN"] != getenv("AUTH_TOKEN")) {
    exit('{"error" : "auth"}');
}

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    exit('{"error" : "method invalid"}');
}

$input = file_get_contents("php://input");
$output =  "La pergunta?";
$error = false;

try {

    $connect = new Client([
        'http_errors'     => false,
        'debug'           => false,
        'timeout'         => 30,
        'connect_timeout' => 30,
        'request.options' => [
            'exceptions' => true
        ]
    ]);

    $response = $connect->request(
        'POST',
        "https://gateway.watsonplatform.net/assistant/api/v1/workspaces/". getenv("IBM_WORKSPACE") ."/message?version=2018-02-16",
        [
            "body"    => '{"input":{"text": "'.$input.'"}}',
            'headers' => ['content-type' => 'application/json'],
            "auth"    => [getenv("IBM_USERNAME"), getenv("IBM_PASSWORD")]
        ]
    );

    $status = $response->getStatusCode();
    $content = $response->getBody()->getContents();
    $content = json_decode($content, true);

    if ($status == 200) {
        if (isset($content["output"]) && isset($content["output"]["text"])) {
            $output = $content["output"]["text"][0];
        }
    } else {
        $error = true;
        $output = $content["error"];
    }

} catch (\Exception $e) {
    $output = $e->getMessage();
    $error = true;
}

header('Content-Type: application/json');
print json_encode(["error" => false, "message" => $output]);
