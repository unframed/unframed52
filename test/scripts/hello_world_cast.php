<?php

require_once('hello_world_invalidate.php');

if (unframed_is_server_script(__FILE__)) {
	require_once('../../src/cast_json.php');
    unframed_cast_json('hello_world_invalidate');
}
