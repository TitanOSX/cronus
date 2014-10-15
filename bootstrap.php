<?php

/**
 *  Mike Mackintosh
 */


// Load SlimAuth stuff
use JeremyKendall\Password\PasswordValidator as Auth_Validator;
use JeremyKendall\Slim\Auth\Adapter\Db\PdoAdapter as Auth_DBAdapter;
use JeremyKendall\Slim\Auth\Bootstrap as Auth_Bootstrap;


// Load Composer Autoload
require_once('vendor/autoload.php');

// Set cookies to httponly
ini_set( 'session.cookie_httponly', 1 );

// Set NYC as the default timezone
date_default_timezone_set( 'America/New_York' );

// Start session tracking
session_start();


// Try/Catch block
try{


    // Init Slim
    $app = new \Slim\Slim();

    // Set Configurations
    $app->config(array(
        'debug' => true,
        'templates.path' => 'views/',
        'view' => new \Slim\Views\Twig(),
        'cookies.encrypt' => true,
        'cookies.secret_key' => '(mz nJAk1-_ 912= kakcm!9zka:!,c)  =',        
    ));

    
    // Validator Library for Forms
    $app->container->singleton('validate', function () {
        return new Respect\Validation\Validator;
    });


    // Assign user
    $app->user = (is_array($_SESSION['user']) ? 
                        $_SESSION['user'] : ['authed' => false, 'username' => 'Guest']);
    $app->twig = $app->view()->getEnvironment();
    $app->twig->addGlobal('user', $app->user);
    

    // Set security headers
    $app->response()->header('X-Content-Type-Options', 'nosniff');
    $app->response()->header('X-XSS-Protection', '1; mode=block');
    $app->response()->header('X-Frame-Options', 'SAMEORIGIN');


    /**
     * Detect config.ini
     * 
     * If config.ini exists, then`
     * create the database connection
     * else, error
     **/
    if(file_exists('config.ini')){
        $config = parse_ini_file('config.ini');
        $app->db = new \bakery\orm($config['host'], $config['name'], 
                    $config['user'], $config['password'], $config['driver']);

        /*$auth_adapter = new Auth_DBAdapter(
            $app->db, 
            'users', 
            'email', 
            'authentication_token', 
            new Auth_Validator()
        );        

        $authBootstrap = new Auth_Bootstrap($app, $auth_adapter, new \Bakery\Auth\Acl());
        $authBootstrap->bootstrap();  */      
    }
    else{
        $app->render('status/no_database.twig');
        die();
    }

    
    // Add Slim Session Middleware
    $app->add(new \Slim\Middleware\SessionCookie());


    // Set up default 404 
    ///*
    $app->notFound(function () use ($app) {
        $app->render('404.html');
    });
    //*/


    // Autoload Routes
    foreach(
            new RecursiveIteratorIterator(
               new RecursiveDirectoryIterator(
                    './lib/routes/'
               )
            ) as $file){
        //
        if(is_file($file->getPathname())){
            require_once $file->getPathname();
        }
    }
    
    /**
     * Generate CSFR
     * 
     * @key is unique csrf token key
     * 
     * returns password_hash
     **/
    function generate_csrf( $key = NULL ){
        
        if(is_null($key)){
            $key = substr(md5(microtime(true)), 2, 8);
        }

        $nonce = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $_SESSION["nonce_key_{$key}"] = $nonce;
        
        return password_hash( $nonce, PASSWORD_BCRYPT, ["cost" => 13] );
    }

    /**
     * Validate CSFR
     * 
     * @key is unique csrf token key
     * 
     * returns boolean true/false
     **/
    function validate_csrf( $key = NULL, $field='hdnonce_' ){

        if(password_verify($_SESSION["nonce_key_{$key}"], $_POST[$field])){
            return true;
        }   

        return false;
    }

    /**
     * 
     **/
    function create_slug($slug, $hyphenate = true){
        $slug = strtolower($slug);

        if($hyphenate){
            $slug = preg_replace("/[-\s\W]/","-",$slug);
        }

        return preg_replace("/[^a-z0-9-]/", "",strtolower($slug));
    }  


    /** Login route MUST be named 'login' **/
    $app->map('/login', function () use ($app) {
        
        $username = null;

        if ($app->request()->isPost()) {
            if(!validate_csrf('login')){
                $app->flashNow('error', 'Please try your request again.');
            }
            $username = $app->request->post('login');
            $password = $app->request->post('authentication_token');

            $result = $app->authenticator->authenticate($username, $password);

            if ($result->isValid()) {
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['authed'] = true;
                $app->redirect('/');
            } else {
                $messages = $result->getMessages();
                $app->flashNow('error', $messages[0]);
            }
        }

        $app->render('login.twig', array('login' => $username, "nonce" => generate_csrf('new_request')));
    })->via('GET', 'POST')->name('login');
    

    /** Logout **/
    $app->get('/logout', function () use ($app) {
        unset($_SESSION['user']);
        $app->authenticator->logout();
        $app->redirect('/');
    });

    
    /** Lets validate that Token Header first **/
    $app->hook('slim.before', function() use ($app){
        if( !isset($app->request->headers['X-Titan-Token']) || 'default' == $app->request->headers['X-Titan-Token']){
            $app->halt(402, 'Token Required');
            return;
        }
    });

    
    /** Lets log **/
    $app->hook('slim.after.router', function () use ($app) {
        $request = $app->request;
        $response = $app->response;

        $app->log->debug(date('Y/M/d H:i:s - ').$response->getStatus().' - '. $request->getMethod().' '. $request->getPathInfo());
    });

    /** Run **/
    $app->run();
}
catch(\Exception $e){
    print_r($e->getMessage());
    //$app->render('error.twig');
}