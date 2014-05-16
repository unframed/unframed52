<?php

require_once(dirname(__FILE__).'/post_json.php');

function unframed_cast_url($uri) {
	return (
		"http".(!empty($_SERVER['HTTPS'])?"s":"")."://"
		.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
		.(!isset($uri)?$_SERVER['REQUEST_URI']:$uri)
		);;
}

/**
 * POST a JSON $request to a $url with HTTP/1.0, return the response's body.
 *
 * @param string $url POSTed to
 * @param int $request JSON object to POST
 * @param in $timeout of the request, defaults to 1 seconds
 *
 * @return string the response's body
 */
function unframed_call_json ($url, $request, $timeout=0.05) {
	$content = json_encode($request);
	$context = stream_context_create(array(
		'http' => array(
			'protocol_version'=>'1.0',
			'method' => 'POST',
			'header' => array(
				"Content-Type: application/json",
				"Content-Length: ".strlen($content),
				"Connection: close"
				),
			'content' => $content,
			'timeout' => $timeout
			)
		));
	@file_get_contents($url, false, $context);
}

/**
 * Handle a POSTed JSON request with $fun ... after a response is sent.
 *
 * @param callable $fun to handle the POSTed JSON request
 * @param int $maxLength of the JSON request body, defaults to 16384 bytes
 * @param in $maxDepth of the JSON request, defaults to 512
 */
function unframed_cast_json ($fun, $maxLength=16384, $maxDepth=512) {
	try {
		$json = unframed_post_json_body($maxLength, $maxDepth);	
	} catch (Unframed $e) {
		http_response_code($e->getCode());
		echo $e->getMessage(), "\n";
	}
	if (isset($json)) {
		http_response_code(200);
		header("Connection: close");
		header("Content-length: 0");
		flush();
		unframed_call($fun, array($json));
		exit(0);
	}
}
