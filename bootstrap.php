<?php

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Header: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self'; font-src 'self'; report-uri https://caspr.io/endpoint/c1d30cf273f2eca63927fc7f29d90c019e0914119aa27e682513a415bfdde082");

require_once('vendor/autoload.php');
$app = new \Slim\Slim();

$app->db = new \bakery\orm('10.211.55.3', 'cdm', 'cdm', 'LOLCAT!', 'mysql');

$app->config(array(
    'debug' => true,
    'templates.path' => 'views/'
));


$app->post('/connect/:serial', function($serial) use ($app){
    $serial = preg_replace("/[^A-Za-z0-9]/", '', $serial);
    
    $device = $app->db->find_devices_by_serial($serial);
    
    if( is_null($device->id) ){
        $app->response->redirect($app->request->getUrl().$app->urlFor('register', ['serial' => $serial]), 307);
        return;
    }

    if(strlen($app->request->post('model')) == 4){
        $model = simplexml_load_string(
                    file_get_contents("http://support-sp.apple.com/sp/product?cc={$app->request->post('model')}")
                 )->configCode;
    }

    $device->serial = $serial;
    $device->uuid = $app->request->post('uuid');
    $device->make = $app->request->post('make');
    $device->model = $model;
    $device->cpu_type = $app->request->post('cpu_type');
    $device->cpu_speed = $app->request->post('cpu_speed');
    $device->physical_memory = $app->request->post('physical_memory');
    $device->os_version = $app->request->post('os_version');
    $device->os_build = $app->request->post('os_build');    

    if($device->save()){
        echo "Yay!";
    }
    
    //print $serial;
})->name('connect');

$app->post('/register/:serial', function($serial) use ($app){
    $device = $app->db->find_devices_by_serial($serial);
    
    $device->serial = $serial;

    if(strlen($app->request->post('model')) == 4){
        $model = simplexml_load_string(
                    file_get_contents("http://support-sp.apple.com/sp/product?cc={$app->request->post('model')}")
                 )->configCode;
    }

    $device->uuid = $app->request->post('uuid');
    $device->make = $app->request->post('make');
    $device->model = $model;
    $device->cpu_type = $app->request->post('cpu_type');
    $device->cpu_speed = $app->request->post('cpu_speed');
    $device->physical_memory = $app->request->post('physical_memory');
    $device->os_version = $app->request->post('os_version');
    $device->os_build = $app->request->post('os_build');
    
    
    if($device->save()){
        echo "Yay!";
    }

})->name("register");

/*
$app->notFound(function () use ($app) {
    $app->render('404.html');
});
//*/
// slim.after.dispatch would probably work just as well. Experiment
$app->hook('slim.before.router', function () use ($app) {
    $request = $app->request;
    $response = $app->response;

    $app->log->debug(date('Y/M/d H:i:s - ').$response->getStatus().' - '. $request->getPathInfo());
});

$app->run();