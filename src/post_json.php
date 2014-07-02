<?php 

require_once(dirname(__FILE__).'/get_json.php');

unframed_no_script(__FILE__);

/**
 * Returns the JSON body of $maxLength bytes from a POST request, decoded as an array of 
 * $maxDepth and wrapped in an UnframedMessage or throw an exception.
 *
 * @param int $maxLength the maximum length of the request JSON body
 * @param int $maxDepth the maximum depth of the request JSON object
 * @param int $options passed to json_decode
 *
 * Note that the JSON_BIGINT_AS_STRING option is allways set for json_decode.
 *
 * @return UnframedMessage
 */
function unframed_post_json_body($maxLength=16384, $maxDepth=512, $options=0) {
    if ($_SERVER['REQUEST_METHOD']!=='POST') {
        throw new Unframed('Method Not Allowed', 405);
    }
    $body = file_get_contents('php://input', NULL, NULL, NULL, $maxLength);
    if (defined('JSON_BIGINT_AS_STRING')) {
        $json = json_decode($body, TRUE, $maxDepth, $options|JSON_BIGINT_AS_STRING);
    } else {
        $json = json_decode($body, TRUE);
    }
    if ($json === NULL) {
        throw new Unframed(json_last_error_msg(), 400);
    } else {
        return unframed_message($json);
    }
}

/**
 * Apply a $fun that handles the parsed JSON body of a POST request and returns
 * an array that will be sent as a JSON body in the HTTP response.
 *
 * @param function $fun the function to apply
 * @param bool $iolist wether the response is a list of JSON strings, default to FALSE 
 * @param int $maxLength the maximum length of the request JSON body
 * @param int $maxDepth the maximum depth of the request JSON object
 *
 * @return void
 */
function unframed_post_json($fun, $iolist=FALSE, $maxLength=16384, $maxDepth=512) {
    try {
        unframed_ok_json(unframed_call(
            $fun, array(unframed_post_json_body($maxLength, $maxDepth))
            ), 0, $iolist);
    } catch (Unframed $e) {
        unframed_error_json($e);
    }
}
