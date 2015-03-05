<?php

require_once('../../deps/JSONMessage.php/src/JSONMessage.php');
require_once('../../src/Unframed.php');

function hello_world(JSONMessage $message) {
    return array(
        "hello" => $message->getString('who', "World")."!"
    );
}

if (unframed_is_server_script(__FILE__)) {
    require_once('../../src/get_json.php');
    unframed_get_json('hello_world');
}
?>
