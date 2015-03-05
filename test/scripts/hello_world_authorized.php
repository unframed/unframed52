<?php

require_once('hello_world.php');

function could_be_json_object ($headers, $body) {
    $bodyCount = count($body);
    if (!(
        count($body) > 1
        && $body[0] == '{'
        && $body[$bodyCount-1] == '}'
    )) {
        throw new Unframed('Not a JSON object', 400);
    }
}

if (unframed_is_server_script(__FILE__)) {
    require_once('../../src/post_json.php');
    unframed_post_json('hello_world', 512, 1, 'could_be_json_object');
}
