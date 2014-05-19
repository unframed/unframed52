<?php

require '../../src/cast_json.php';
require '../../src/properties.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') { // Send cast message

	function unframed_cast_test ($request) {
		$r = unframed_properties($request);
		touch('unframed_cast_test');
		$time = time();
		unframed_cast(unframed_cast_url(), $request, $r->asFloat('timeout', 0.05));
		sleep($r->asFloat('sleep', 3) + 1);
		return array(
			'pass' => !file_exists('unframed_cast_test'),
			'slept' => time() - $time
			);
	}
	unframed_get_json ('unframed_cast_test');

} elseif ($method == 'POST') { // Receive cast message

	function unframed_cast_test ($request) {
		$r = unframed_properties($request);
		sleep($r->asFloat('sleep', 3) - $r->asFloat('timeout', 0.05));
		unlink('unframed_cast_test');
	}
	unframed_cast_json('unframed_cast_test');

} else { // 405

	unframed_json_error(new Unframed('Invalid Method', 405));

}
