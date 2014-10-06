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
function unframed_is_server_script ($filename) {
    return realpath($_SERVER['SCRIPT_FILENAME']) == $filename;
}

/**
 * If $filename is the server script send a response with no content,
 * by default reply with a 404 (Not Found) error code.
 */
function unframed_no_script ($filename, $code=404) {
    if (unframed_is_server_script($filename)) {
        http_response_code($code);
        header("Content-length: 0");
        flush();
        die();
    }
}

/**
 * Test basic requirements of Unframed52, return TRUE on success.
 */
function unframed_test () {
    return (
        version_compare(PHP_VERSION, '5.2.0') >= 0 &&
        ignore_user_abort() == TRUE
        );
}

/**
 * Recompile the PHP sources in $filename and invalidate the APC or OP cache.
 */
function unframed_compile ($filename) {
    // test Zend's opcache_invalidate first
    if (function_exists('opcache_invalidate')) {
        return opcache_invalidate($filename);
    } elseif (function_exists('apc_compile_file')) {
        return apc_compile_file($filename);
    }
    return TRUE;
}

function unframed_site_url() {
    return (
        "http".(!empty($_SERVER['HTTPS'])?"s":"")."://"
        .$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
        );
}

function unframed_configuration () {
    return dirname(__FILE__).'/.config-'.md5(unframed_site_url()).'.php';
}

/**
 * Update `.config.php` (and eventually recompile and cache).
 */
function unframed_configure ($concurrent, $cast_timeout, $loop_timeout) {
    $filename = unframed_configuration();
    if (file_put_contents($filename, "<?php\n"
        ."if (realpath(\$_SERVER['SCRIPT_FILENAME']) == __FILE__) {\n"
        ."    header('x', TRUE, 404);\n"
        ."    die();\n"
        ."}\n"
        ."define('UNFRAMED_CONCURRENT', ".$concurrent.");\n"
        ."define('UNFRAMED_CAST_TIMEOUT', ".$cast_timeout.");\n"
        ."define('UNFRAMED_LOOP_TIMEOUT', ".$loop_timeout.");\n"
        ."?>") !== FALSE) {
        return unframed_compile($filename);
    }
    return FALSE;
}

@include_once(unframed_configuration());
if (!defined('UNFRAMED_CONCURRENT')) {
    if (unframed_configure(8, 0.005, 29)) {
        require(unframed_configuration());
    }
}