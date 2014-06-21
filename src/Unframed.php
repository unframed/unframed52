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

if (!function_exists('http_response_code')) {
    function http_response_code($code) {
        header('x', TRUE, $code);
    }
}

/**
 * Log an error $message about a $value.
 *
 * @param $message to log
 * @param $value to print
 *
 * @return TRUE on success, FALSE otherwise
 */
function unframed_debug($message, $value) {
    return error_log($message.' - '.var_export($value, true));
}

/**
 * Fail fast to an HTTP error response, make sure to support nested exceptions.
 */
if (method_exists(new Exception(), 'getPrevious')) {
    /**
     * Extends the base `Exception` class, set the default code to 500.
     */
    class Unframed extends Exception {
        public function __construct($message, $code=500, Exception $previous=NULL) {
            parent::__construct($message, $code, $previous);
        }
    }
} else {
    /**
     * Extends the base `Exception` class, set the default code to 500 and provide
     * a shim for `Exception::getPrevious`.
     */
    class Unframed extends Exception {
        private $previous = NULL;
        public function __construct($message, $code=500, Exception $previous=NULL) {
            parent::__construct($message, $code);
            $this->previous = $previous;
        }
        /**
         * Get the nested exception.
         */
        function getPrevious() {
            return $this->previous;
        }
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

/**
 * Test if the real path to the SCRIPT_FILENAME is $filename.
 *
 * @param string $filename tested
 *
 * @return TRUE if $filename is the real path to SCRIP_FILENAME
 */
function unframed_main ($filename) {
    return realpath($_SERVER['SCRIPT_FILENAME']) == $filename;
}

/**
 * If this __FILE__ is the script run, test basic requirements of Unframed52
 * and reply with 204 No Content on success or 500 Server Error on failure.
 */
if (unframed_main(__FILE__)) {
    if (version_compare(PHP_VERSION, '5.2.0') >= 0 && ignore_user_abort() == TRUE) {
        http_response_code(204); // No content, test pass
    } else {
        http_response_code(500); // Server error, test fail
    }
}