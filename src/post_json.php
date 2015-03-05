<?php

require_once(dirname(__FILE__).'/get_json.php');

unframed_no_script(__FILE__);

/**
 * Returns the JSON body of $maxLength bytes from a POST request.
 *
 * @param int $maxLength the maximum length of the request JSON body
 *
 * @return string body of a POST request
 */
function unframed_post_json_body ($maxLength) {
    return file_get_contents('php://input', NULL, NULL, NULL, $maxLength);
}

function unframed_post_json_message ($body, $maxDepth) {
    $message = JSONMessage::parse($body, $maxDepth);
    if ($message === NULL) {
        throw new Unframed(json_last_error_msg(), 400);
    }
    return $message;
}

/**
 * Apply a $fun that handles the parsed JSON body of a POST request and returns
 * an array that will be sent as a JSON body in the HTTP response.
 *
 * @param function $fun the function to apply
 * @param int $maxLength the maximum length of the request JSON body
 * @param int $maxDepth the maximum depth of the request JSON object
 *
 * @return void
 */
function unframed_post_json($fun, $maxLength=16384, $maxDepth=512, $authorize=NULL) {
    try {
        if ($_SERVER['REQUEST_METHOD']!=='POST') {
            throw new Unframed('Method Not Allowed', 405);
        }
        $body = unframed_post_json_body($maxLength);
        if ($authorize !== NULL) {
            call_user_func_array($authorize, array(get_headers(), $body));
        }
        $message = unframed_post_json_message($body, $maxDepth);
        $response = call_user_func_array($fun, array($message));
        unframed_ok_json($response);
    } catch (Exception $e) {
        unframed_error_json($e);
    }
}
