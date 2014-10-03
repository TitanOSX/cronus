<?php 

$app->post('/api/register/:serial', function($serial) use ($app){

    if(!strstr($_SERVER['HTTP_USER_AGENT'], "titanOSX")){
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
        
        $response['X-Powered-By'] = 'titanOSX_CDM';
        $response->status(200);
        // etc.

        echo json_encode(["response" => "successful", "error" => null]);
    }
    else{
        echo json_encode(["response" => "failure", "error" => "_errorSavingRecord"]);        
    }

})->name("api-register");

/**
 * 
 **/
$app->delete('/api/unregister/:serial', function($serial) use ($app){
    $device = $app->db->find_devices_by_serial($serial);
    
    if(!$device->exists()){
        $app->halt(404, 'Not Found');
    }

    $device->delete();

    $app->halt(410, 'Gone');

})->name('api-unregister'); 
