<?php

require '../../deps/JSONMessage.php/src/JSONMessage.php';
require '../../src/cast_json.php';

function unframed_test_log ($message) {
	error_log($message->encoded());
}

unframed_cast_json('unframed_test_log');