<?php

/**
 * Unframed's very core: to require a minimum of configuration and fail fast to HTTP.
 *
 * @author Laurent Szyster
 */

/**
 * First, let the script run through after its input is closed.
 *
 * This ensure that network timeouts don't prevent longer running 
 * processes like static resources generation or interrupt
 * database transactions without a rollback.
 */

ignore_user_abort(true);

// Second, shim PHP 5.2

if (!function_exists('http_response_code')) {
    function http_response_code($code) {
        header('x', TRUE, $code);
    }
}

/**
 * Log a debug $message about a $value 
 */
function unframed_debug($message, $value) {
    return error_log($message.' - '.var_export($value, true));
}

/**
 * Unframed extends Exception
 *
 * Fail fast to an HTTP error response.
 * 
 */
class Unframed extends Exception {
    public function __construct($message, $code=500, Exception $previous=null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Apply the callable $fun with $array, throw an exception if the $fun is
 * the name of an undefined function, if $fun is not callable and or if
 * $array is not an array.
 *
 * @param callable $fun the callable to apply 
 * @param array $array the arguments to use
 *
 * @return any result of `call_user_func_array($fun, $array)`
 * @throws Unframed 
 */
function unframed_call ($fun, $array) {
    if (!is_array($array)) {
        throw new Unframed('Type Error - '.var_export($array, TRUE).' is not an array');
    }
    if (is_string($fun)) {
        if (function_exists($fun)) {
            return call_user_func_array($fun, $array);
        } else {
            throw new Unframed('Name Error - '.$fun);
        }
    } elseif (is_callable($fun)) {
        return call_user_func_array($fun, $array);
    } else {
        throw new Unframed('Type Error - '.var_export($fun, TRUE).' is not callable');
    }
}
