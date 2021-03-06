<?php
use Dotenv\Dotenv;
use Laminas\Diactoros\Response\JsonResponse;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('TO_NUMBER', getenv('TO_NUMBER'));
define('VONAGE_NUMBER', getenv('VONAGE_NUMBER'));

$app = new \Slim\App();

$app->get('/webhooks/answer', function (Request $request, Response $response) {
    //Get our public URL for this route
    $uri = $request->getUri();
    $url = $uri->getScheme() . '://'.$uri->getHost() . ($uri->getPort() ? ':'.$uri->getPort() : '') . '/webhooks/recording';

    $record = new \Vonage\Voice\NCCO\Action\Record();
    $record->setEventWebhook(new \Vonage\Voice\Webhook($url));
    $record->setChannels(2);

    $connect = new \Vonage\Voice\NCCO\Action\Connect(new \Vonage\Voice\Endpoint\Phone(TO_NUMBER));
    $connect->setFrom(VONAGE_NUMBER);

    $ncco = new \Vonage\Voice\NCCO\NCCO();
    $ncco->addAction($connect);
    $ncco->addAction($record);

    return new JsonResponse($ncco);
});

$app->post('/webhooks/recording', function (Request $request, Response $response) {
    /** @var \Vonage\Voice\Webhook\Record */
    $recording = \Vonage\Voice\Webhook\Factory::createFromRequest($request);
    error_log($recording->getRecordingUrl());

    return $response->withStatus(204);
});

$app->run();
