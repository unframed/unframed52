<?php

require '../../src/fold_json.php';
require '../../src/properties.php';


$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') { // Send cast message

	function unframed_fold_count () {
		return count(glob('unframed_fold_test_*')); 
	}

	function unframed_fold_test ($request) {
		$r = unframed_properties($request);
		$wait = $r->asFloat('wait', 10);
		$timeout = $r->asFloat('timeout', 0.05);
		$L = $r->asInt('concurrent', 2);
		$messages = array();
		for ($i = 0 ; $i < $L; $i++) {
			array_push($messages, array('filename'=>'unframed_fold_test_'.$i));
		}
		foreach($messages as $message) {
			touch($message['filename']);
		}
		$time = time();
		unframed_cast(unframed_cast_url(), $messages, $timeout);
		// sleep($timeout);
		while (unframed_fold_count() > 0 && time()-$time < $wait) {
			sleep($timeout);
		}
		$failed = glob('unframed_fold_test_*');
		return array(
			'pass' => count($failed)==0,
			'slept' => time() - $time,
			'failed' => $failed,
			'concurrent' => $L,
			);
	}
	unframed_get_json ('unframed_fold_test');

} elseif ($method == 'POST') { // Receive cast message

	function unframed_fold_test ($message, $rest) {
		sleep(1);
		unlink($message['filename']);
	}
	unframed_fold_json('unframed_fold_test');

} else { // 405

	unframed_json_error(new Unframed('Invalid Method', 405));

}
