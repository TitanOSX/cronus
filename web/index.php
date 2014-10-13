<?php
// Local Router for PHP Dev Stuff
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

define('CDM_ROOT', dirname(dirname(__FILE__)));

chdir(CDM_ROOT);
require_once(CDM_ROOT.'/bootstrap.php');
