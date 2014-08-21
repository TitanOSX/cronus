<?php

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Header: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self'; font-src 'self'; report-uri https://caspr.io/endpoint/c1d30cf273f2eca63927fc7f29d90c019e0914119aa27e682513a415bfdde082");

require_once('vendor/autoload.php');
$app = new \Slim\Slim();

$app->db = new \bakery\orm('10.1.4.231', 'cdm', 'cdm', 'LOLCAT!', 'mysql');

$app->config(array(
    'debug' => true,
    'templates.path' => 'views/'
));

$app->post('/connect/:serial', function($serial) use ($app){
    $serial = preg_replace("/[^A-Za-z0-9]/", '', $serial);
    $device = $app->db->findOrCreate('devices', 'serial', $serial);
    $device->serial = $serial;
    $device->date_modified = time();

    print_r($device);

    $device->save();
    //print $serial;
});

/*
$app->notFound(function () use ($app) {
    $app->render('404.html');
});
//*/
// slim.after.dispatch would probably work just as well. Experiment
$app->hook('slim.after.router', function () use ($app) {
    $request = $app->request;
    $response = $app->response;

    $app->log->debug(date('Y/M/d H:i:s - ').$response->getStatus().' - '. $request->getPathInfo());
    // And so on ...
});

$app->run();