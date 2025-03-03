<?php
require 'vendor/autoload.php';
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = AppFactory::create();

$app->post('/webhook', function (Request $request, Response $response) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);

    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
    if (!validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)) {
        return $response->withStatus(400, 'Invalid signature');
    }

    foreach ($data['events'] as $event) {
        if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
            replyMessage($event['replyToken'], $event['message']['text']);
        }
    }

    return $response->withJson(['status' => 'success']);
});

function validateSignature($body, $secret, $signature) {
    return hash_equals(base64_encode(hash_hmac('sha256', $body, $secret, true)), $signature);
}

function replyMessage($replyToken, $message) {
    $client = new Client();
    $client->post('https://api.line.me/v2/bot/message/reply', [
        'headers' => [
            'Authorization' => 'Bearer ' . $_ENV['CHANNEL_ACCESS_TOKEN'],
            'Content-Type' => 'application/json'
        ],
        'json' => [
            'replyToken' => $replyToken,
            'messages' => [['type' => 'text', 'text' => $message]]
        ]
    ]);
}

$app->run();
