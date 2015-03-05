<?php

/**
 * Test basic requirements of Unframed52, return TRUE if the version of PHP 5.2+.
 */
function unframed_test () {
    return (version_compare(PHP_VERSION, '5.2') >= 0);
}

// The shims required to support PHP since 5.2 ,-)

/**
 * Unframed's very core: to require a minimum of configuration and fail fast to HTTP.
 *
 * @author Laurent Szyster
 */

if (!function_exists('http_response_code')) {
    function http_response_code($code) {
        header('x', TRUE, $code);
    }
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
 * Test if the real path to the SCRIPT_FILENAME is $filename.
 *
 * @param string $filename tested
 *
 * @return TRUE if $filename is the real path to SCRIP_FILENAME
 */
function unframed_is_server_script ($filename) {
    if (!(
        isset($_SERVER)
        && isset($_SERVER['SCRIPT_FILENAME'])
    )) {
        return FALSE;
    }
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

unframed_no_script(__FILE__);

// unframed52's PHP bytecode cache invalidation, require this once ,-)

// Zend's opcache first, APC second and TRUE without both.

if (function_exists('opcache_invalidate')) {
    function unframed_invalidate_php ($filename, $sources) {
        file_put_contents($filename, $sources);
        return opcache_invalidate($filename);
    }
} elseif (function_exists('apc_compile_file')) {
    function unframed_invalidate_php ($filename, $sources) {
        file_put_contents($filename, $sources);
        return apc_compile_file($filename);
    }
} else {
    function unframed_invalidate_php ($filename, $sources) {
        file_put_contents($filename, $sources);
        return file_exists($filename);
    }
}

function unframed_site_url() {
    return (
        "http".(!empty($_SERVER['HTTPS'])?"s":"")."://"
        .$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
        );
}

function unframed_site_key() {
    return md5(unframed_site_url());
}

function unframed_remote_is_local () {
    $remote = $_SERVER['REMOTE_ADDR'];
    return ($remote == '127.0.0.1' || $remote == $_SERVER['SERVER_ADDR']);
}

function unframed_realpath ($path, $base=NULL) {
    return realpath(($base === NULL ? dirname(__FILE__): $base).'/'.$path);
}

