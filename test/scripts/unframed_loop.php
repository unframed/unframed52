<?php

require '../../deps/JSONMessage.php/src/JSONMessage.php';
require '../../src/loop_json.php';

unframed_cast_control(new UnframedLoop(array(
	'/test/unframed_test_log.php',
	'/test/hello_world_cast.php'
)));