<?php
require '../../deps/JSONMessage.php/src/JSONMessage.php';
require '../../src/Unframed.php';

function unframed_fail_fast(JSONMessage $message) {
    throw new Exception('Failed Fast');
}

if (unframed_is_server_script(__FILE__)) {
    require_once('../../src/get_json.php');
    unframed_get_json('unframed_fail_fast');
}
