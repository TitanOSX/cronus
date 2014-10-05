<?php

$app->get('/api/observer/:serial', function($serial) use ($app){
    $device = $app->db->find_devices_by_serial($serial);
    if($device->exists()){
        $app->halt(203, 'Send er Up');
    }

    $app->halt('404', 'Device Not Registered');
});

$app->post('/api/observer/:serial', function($serial) use ($app){
    
    # Set Response Object
    $response = $app->response();

    if(!strstr($_SERVER['HTTP_USER_AGENT'], "titanOSX")){
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
    if(!$device->exists()){
        $app->halt('404', 'Device Not Registered');
    }

    $module_data = json_decode($stream);
    $table = $module_data->module;
    
    try{
        $module_template = $app->db->create("module_".$table);
    
        $successes = 0;
        $required = sizeof($module_data->data);

        foreach($module_data->data as $row){
            $module = $module_template;

            $module->device_id = $device->id;
            $module->audit_date = $row->_unixtime;

            foreach($row as $column => $value){
                if($module->hasColumn($column)){
                    $module->{$column} = $value;
                }
            }

            $module->save();
            if($module->isNew()){
                $successes++;
            }
        }
    }
    catch(\Exception $e){
        $response->status(403);        
        $response->body(json_encode(["response" => "failure", "error" => "_moduleSchemaDoesNotExist", "debug" => $e->getMessage()]));                
        return;
    }

    if($required == $successes){       
        $response['X-Powered-By'] = 'titanOSX_CDM';
        $response->status(202);
        // etc.

        $response->body(json_encode(["response" => "successful", "error" => null]));
    }
    else{
        $response->status(504);        
        $response->body(json_encode(["response" => "failure", "error" => "_errorUploadingModuleData"]));        
    }

})->name("api-observer");