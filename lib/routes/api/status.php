<?php

/**
 * 
 **/
$app->get('/api/status/:serial', function($serial) use ($app){
    $device = $app->db->find_devices_by_serial($serial);
    
    if(!$device->exists()){
        echo "Doesnt exist";
        $app->response()->status(404);
        return;
    }

    $app->response()->status(200);
    die( json_encode( $device->fetchAll() ) );

})->name('get-api-status'); 

/**
 * 
 **/
$app->post('/api/status/:serial', function($serial) use ($app){
    $device = $app->db->find_devices_by_serial($serial);
    
    if(!$device->exists()){
        $app->response()->redirect($app->urlFor('api-register', ['serial' => $serial]), 307);
        return;
    }
})->name('api-status'); 