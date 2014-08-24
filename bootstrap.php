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
    
    if(!strstr($_SERVER['HTTP_USER_AGENT'], "ctznOSX")){
        die("_invalidRequest");
    }
    
    $app->response['Content-Type'] = 'application/json';    

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
        $response = $app->response();
        
        $response['X-Powered-By'] = 'ctznOSX_CDM';
        $response->status(200);
        // etc.

        $response->body(json_encode(["response" => "successful", "error" => null]));
    }
    else{
        $response->body(json_encode(["response" => "failure", "error" => "_errorSavingRecord"]));        
    }
    
    //print $serial;
})->name('connect');

$app->post('/register/:serial', function($serial) use ($app){

    if(!strstr($_SERVER['HTTP_USER_AGENT'], "ctznOSX")){
        die("_invalidRequest");
    }
    
    $app->response['Content-Type'] = 'application/json';   

    # Create new device if it doesnt exist
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
        $response = $app->response();
        
        $response['X-Powered-By'] = 'ctznOSX_CDM';
        $response->status(200);
        // etc.

        $response->body(json_encode(["response" => "successful", "error" => null]));
    }
    else{
        $response->body(json_encode(["response" => "failure", "error" => "_errorSavingRecord"]));        
    }

})->name("register");


$app->post('/observer/:serial', function($serial) use ($app){
    
    # Set Response Object
    $response = $app->response();

    if(!strstr($_SERVER['HTTP_USER_AGENT'], "ctznOSX")){
        die("_invalidRequest");
    }

    if($_POST['ping'] == 'ping'){
        $response->status(203);
        $response->body(json_encode(["response" => "successful", "error" => null]));        
        return;
    }

    if( hash("sha256", $_POST['stream']) != $_POST['digest']){
        $response->status(417);        

        $response->body(json_encode(["response" => "failure", "error" => "_couldNotValidateStream"]));        
        return;
    }
    
    $app->response['Content-Type'] = 'application/json';   

    $stream = zlib_decode($_POST['stream']);

    # Create new device if it doesnt exist
    $device = $app->db->find_devices_by_serial($serial);

    $module_data = json_decode($stream);
    $table = $module_data->module;

    try{
        $module_template = $app->db->create("module_".$table);
    

        $successes = 0;
        $required = sizeof($module_data->data);

        foreach($module_data->data as $row){
            $module = $module_template;

            $module->device_id = $device->id;

            foreach($row as $column => $value){
                if($module->hasColumn($column)){
                    $module->{$column} = $value;
                }
            }
            if($module->save()){
                $successes++;
            }
        }
    }
    catch(\Exception $e){
        $response->status(403);        
        $response->body(json_encode(["response" => "failure", "error" => "_moduleSchemaDoesNotExist"]));                
        return;
    }

    if($required == $successes){       
        $response['X-Powered-By'] = 'ctznOSX_CDM';
        $response->status(202);
        // etc.

        $response->body(json_encode(["response" => "successful", "error" => null]));
    }
    else{
        $response->status(504);        
        $response->body(json_encode(["response" => "failure", "error" => "_errorUploadingModuleData"]));        
    }

})->name("observer");

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