<?php

require '../../deps/JSONMessage.php/src/JSONMessage.php';
require '../../src/get_json.php';

function unframed_info ($message) {
    session_start();
    header('Cache-Control: no-cache, must-revalidate');
    return array(
        'request' => $_REQUEST,
        'session' => $_SESSION,
        'server' => $_SERVER,
        'phpVersion' => phpVersion(),
        'get_current_user' => get_current_user(),
        'sys_get_temp_dir' => sys_get_temp_dir(),
        'get_included_files' => get_included_files(),
        'PDO::getAvailableDrivers' => PDO::getAvailableDrivers(),
        'get_loaded_extensions' => get_loaded_extensions()
        );
}

unframed_get_json('unframed_info');
