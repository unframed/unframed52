<?php

require_once('hello_world.php');
require_once('../../src/www_invalidate.php');

function hello_world_json (JSONMessage $message) {
    return json_encode(hello_world($message));
}

function hello_world_invalidate (JSONMessage $message) {
    $who = $message->getString('who', 'World');
    return array('invalidate' => unframed_www_invalidate($message, array(
        'hello/'.$who.'.json' => 'hello_world_json',
        'hello/'.$who.'.html' => 'hello_world_html.php'
    )));
}

if (unframed_is_server_script(__FILE__)) {
    require_once('../../src/get_json.php');
    unframed_get_json('hello_world_invalidate');
}
?>
