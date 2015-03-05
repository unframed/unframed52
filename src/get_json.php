<?php

require_once(dirname(__FILE__).'/Unframed.php');

unframed_no_script(__FILE__);

function unframed_http_json ($code, $body) {
    http_response_code($code);
    header("Content-length: ".strlen($body));
    header('Content-Type: application/json');
    header("Cache-Control: no-cache, no-store: 0");
    echo $body;
    flush();
}

/**
 * Set the appropriate HTTP response headers, let PHP set the HTTP response code
 * and send a JSON response body. Note that if 'application/json' is not set in
 * $_SERVER['HTTP_ACCEPT'] then the JSON will be pretty printed.
 *
 * @param array $json the JSON response, may be an list of JSON encoded strings
 * @param int $options passed to json_encode, default to 0
 *
 * @return void
 *
 * @throws Unframed
 */
function unframed_ok_json($json, $options=0) {
    if (JSONMessage::is_list($json)) {
        $body = '['.implode(',', $json).']';
    } else {
        if (defined('JSON_PRETTY_PRINT')) {
            $accept = $_SERVER['HTTP_ACCEPT'];
            if (preg_match('/application.json/i', $accept) < 1) {
                $options = $options | JSON_PRETTY_PRINT;
            }
            $body = json_encode($json, $options);
        } else {
            $body = json_encode($json);
        }
        if (!is_string($body)) {
            throw new Unframed(json_last_error_msg());
        }
    }
    unframed_http_json(200, $body);
}

/**
 * ...
 */
function unframed_error_json($e) {
    if (get_class($e) === 'Unframed') {
        $code = $e->getCode(); // Get the Unframed's HTTP error code
    } else {
        $code = 500; // Set the HTTP error code to 500 for other exceptions
    }
    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        // No error response body for HEAD requests
        http_response_code($code);
        flush();
    } else {
        if ($code === 500) {
            // Reply with a JSON trace for 500 Server Error
            $json = array('exception' => array(
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
                ));
        } else {
            // Reply with the exception message for other errors.
            $json = array('error' => $e->getMessage());
        }
        // Allways pretty print when possible, tracebacks should be readable.
        if (defined('JSON_PRETTY_PRINT')) {
            $body = json_encode($json, JSON_PRETTY_PRINT);
        } else {
            $body = json_encode($json);
        }
        // Send a JSON response body for all other methods than HEAD
        unframed_http_json($code, $body);
    }
}

/**
 * Apply a $fun that handles the parsed query string of a GET request and returns
 * an array that will be sent as a JSON body in the HTTP response, catch any Unframed
 * exception, reply with an error code and a JSON error message.
 *
 * @param callable $fun
 */
function unframed_get_json ($fun) {
    try {
        // Fail for any other methods than GET.
        if ($_SERVER['REQUEST_METHOD']!=='GET') {
            throw new Unframed('Method Not Allowed', 405);
        }
        // Box the query parameters (and string ,-) in a JSON message
        $message = new JSONMessage($_GET, $_SERVER['QUERY_STRING']);
        // Handle the JSON message and reply with JSON ...
        unframed_ok_json(call_user_func_array($fun, array($message)));
    } catch (Exception $e) {
        // ... or catch all and fail fast to HTTP error
        unframed_error_json($e);
    }
}