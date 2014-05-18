<?php

require '../../src/cast_json.php';
require '../../src/properties.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') { // Send cast message

	function unframed_cast_test ($request) {
		touch('unframed_cast_test');
		$time = time();
		$r = new UnframedProperties($request);
		$sleep = $r->getFloat('sleep', 3.000);
		unframed_cast(unframed_cast_url(), $request);
		// sleep($sleep+1);
		sleep(4.0);
		return array(
			'pass' => !file_exists('unframed_cast_test'),
			'slept' => time() - $time
			);
	}
	unframed_get_json ('unframed_cast_test');

} elseif ($method == 'POST') { // Receive cast message

	function unframed_cast_test ($request) {
		unframed_debug('cast', time());
		sleep(3.0);
		// sleep($r->getFloat('sleep', 3.000) - $r->getFloat('timeout', 0.050));
		unframed_debug('cast', time());
		unlink('unframed_cast_test');
	}
	unframed_cast_json('unframed_cast_test');

} else { // 405

	unframed_json_error(new Unframed('Invalid Method', 405));

}
