<?php

require '../../src/fold_json.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') { // Send cast message

	function unframed_fold_test ($request) {
		$messages = array();
		$L = (int) $request['concurrent'];
		for ($i = 0 ; $i < $L; $i++) {
			array_push($messages, array('filename'=>'unframed_fold_test_'.$i));
		}
		foreach($messages as $message) {
			touch($message['filename']);
		}
		$time = time();
		unframed_cast(unframed_cast_url(), $messages);
		sleep(2+0.10*count($messages));
		$failed = glob('unframed_fold_test_?');
		return array(
			'pass' => count($failed)==0,
			'slept' => time() - $time,
			'failed' => $failed,
			'messages' => $messages
			);
	}
	unframed_get_json ('unframed_fold_test');

} elseif ($method == 'POST') { // Receive cast message

	function unframed_fold_test ($message, $rest) {
		sleep(1.0);
		unlink($message['filename']);
	}
	unframed_fold_json('unframed_fold_test');

} else { // 405

	unframed_json_error(new Unframed('Invalid Method', 405));

}
