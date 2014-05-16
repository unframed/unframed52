<?php

require_once(dirname(__FILE__).'/cast_json.php');

/**
 * Handle a POSTed JSON request with $fun ... after a response is sent.
 *
 * @param callable $fun to handle the POSTed JSON request
 * @param int $maxLength of the JSON request body, defaults to 16384 bytes
 * @param in $maxDepth of the JSON request, defaults to 512
 */
function unframed_fold_json($fun, $maxLength=16384, $maxDepth=512) {
	try {
		$json = unframed_post_json_body($maxLength, $maxDepth);
	} catch (Unframed $error) {
		http_response_code($e->getCode());
		echo $e->getMessage(), "\n";
	}
	if (isset($json)) {
		$messages = $json['messages'];
		$message = array_pop($messages);
		if (!empty($messages)) {
			unframed_call_json(unframed_cast_url(), $request);
		}
		unframed_ok_json(array('time'=> microtime(TRUE)));
		unframed_call($fun, array($message));
	}
}
