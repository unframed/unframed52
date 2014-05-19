<?php 

require_once(dirname(__FILE__).'/Unframed.php');

if (!function_exists('json_last_error')) {
    function json_last_error_msg() {
        return 'Unknown JSON error';
    }
} elseif (!function_exists('json_last_error_msg')) {
    function json_last_error_msg() {
        static $errors = array(
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        $error = json_last_error();
        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}

/**
 * Returns the query parameters of a GET request as an array.
 *
 * @return array
 *
 * @throws Unframed
 */
function unframed_get_query() {
    if ($_SERVER['REQUEST_METHOD']!=='GET') {
        throw new Unframed('Method Not Allowed', 405);
    } else {
        return $_GET;
    }
}

/**
 * Set the appropriate HTTP response headers, let PHP set the HTTP response code and send a 
 * JSON response body. Note that if 'application/json' is not in the $_SERVER['HTTP_ACCEPT']
 * the JSON will be pretty printed.
 *
 * @param array $json the JSON response
 * @param int $options passed to json_encode
 *
 * @return void
 *
 * @throws Unframed
 */
function unframed_ok_json($json, $options=0) {
    $accept = $_SERVER['HTTP_ACCEPT'];
    if (preg_match('/application.json/i', $accept) < 1) {
        $options = $options | JSON_PRETTY_PRINT;
    }
    $body = json_encode($json, $options);
    if (is_string($body)) {
        http_response_code(200);
        header("Content-length: ".strlen($body));
        header('Content-Type: application/json');
        echo $body;
        flush();
    } else {
        throw new Unframed(json_last_error_msg());
    }
}

/**
 * ...
 */
function unframed_error_json($e) {
    $json = array('Error' => $e->getMessage());
    $body = json_encode($json, JSON_PRETTY_PRINT);
    http_response_code($e->getCode());
    header("Content-length: ".strlen($body));
    header('Content-Type: application/json');
    echo $body;
    flush();
}

/**
 * Apply a $fun that handles the parsed query string of a GET request and returns
 * an array that will be sent as a JSON body in the HTTP response, catch any Unframed
 * exception, reply with an error code and a JSON error message.
 */
function unframed_get_json($fun) {
    try {
        unframed_ok_json(unframed_call($fun, array(unframed_get_query())));
    } catch (Unframed $e) {
        unframed_error_json($e);
    }
}