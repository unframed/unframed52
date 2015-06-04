<?php

require_once(dirname(__FILE__).'/post_json.php');

unframed_no_script(__FILE__);

if (!defined('UNFRAMED_CAST_TIMEOUT')) {
    define('UNFRAMED_CAST_TIMEOUT', 0.005);
}
if (!defined('UNFRAMED_CAST_INTERVAL')) {
    define('UNFRAMED_CAST_INTERVAL', 59.0);
}
if (!defined('UNFRAMED_CAST_CONCURRENT')) {
    define('UNFRAMED_CAST_CONCURRENT', 8);
}

/**
 * Return the complete URL of the given $uri or the script's URL if
 * $uri is NULL.
 *
 * @param string $uri to complete, NULL by default
 */
function unframed_cast_url($uri=NULL) {
    $url = parse_url($uri===NULL?$_SERVER['REQUEST_URI']:$uri);
    return unframed_site_url().$url['path'];
}

/**
 * POST a JSON encoded $content to a $url with HTTP/1.0 and return TRUE if
 * a connection could be established and an HTTP/1.X 200 response was provided.
 *
 * @param string $url POSTed to
 * @param string $content of the encoded JSON object to POST
 * @param array $header a list of HTTP header lines
 * @param int $timeout of the request
 *
 * @return boolean
 */
function unframed_cast_encoded ($url, $content, $header, $timeout) {
    $context = stream_context_create(array(
        'http' => array(
            'protocol_version'=>'1.0',
            'method' => 'POST',
            'header' => $header,
            'content' => $content,
            'timeout' => $timeout
            )
        ));
    $stream = @fopen($url, 'r', false, $context);
    if ($stream !== FALSE) {
        // connection succeed, get the HTTP headers and close
        $meta = stream_get_meta_data($stream);
        $headers = $meta['wrapper_data'];
        fclose($stream);
        // return TRUE if the HTTP response is 200 OK
        return (is_array($headers) && preg_match(
            '/^HTTP\/1.(0|1) 200/', $headers[0]
            ) === 1);
    }
    // connection failed
    return FALSE;
}

/**
 * Cast a $json request to a $url with HTTP/1.0 and return TRUE.
 *
 * @param string $url POSTed to
 * @param array $json JSON object to POST
 * @param array $headers a list of HTTP header lines
 * @param int $timeout of the request, defaults to UNFRAMED_CAST_TIMEOUT
 *
 * @return boolean
 */
function unframed_cast ($url, $json, $headers=array(), $timeout=UNFRAMED_CAST_TIMEOUT) {
    $content = json_encode($json);
    $header = array_merge(array(
        "Content-Type: application/json",
        "Content-Length: ".strlen($content),
        "Connection: close"
        ), $headers);
    return unframed_cast_encoded($url, $content, $header, $timeout);
}

/**
 * Cast the same $json array to all $uris and return the encoded JSON.
 */
function unframed_cast_all ($uris, $json, $headers=array(), $timeout=UNFRAMED_CAST_TIMEOUT) {
    $content = json_encode($json);
    $header = array_merge(array(
        "Content-Type: application/json",
        "Content-Length: ".strlen($content),
        "Connection: close"
        ), $headers);
    foreach ($uris as $uri) {
        unframed_cast_encoded(unframed_cast_url($uri), $content, $header, $timeout);
    }
    return $content;
}

// How to handle a local cast message

/**
 * Send an HTTP 200 response with zero content, flush and continue ...
 */
function unframed_cast_ok () {
    http_response_code(200);
    header("Connection: close");
    header("Content-length: 0");
    header("Cache-Control: no-cache, no-store: 0");
    flush();
}

/**
 * Handle a POSTed JSON request with $fun ... after a response is sent.
 *
 * @param callable $fun to handle the POSTed JSON request
 * @param int $maxLength of the JSON request body, defaults to 16384 bytes
 * @param int $maxDepth of the JSON request, defaults to 512
 *
 * @throws Unframed
 */
function unframed_cast_json ($fun, $maxLength=16384, $maxDepth=512, $authorize=NULL) {
    try {
        if ($_SERVER['REQUEST_METHOD']!=='POST') {
            throw new Unframed('Method Not Allowed', 405);
        } elseif (!unframed_remote_is_local()) {
            throw new Unframed('Access Denied', 403);
        }
        $body = unframed_post_json_body($maxLength);
        if ($authorize !== NULL) {
            call_user_func_array($authorize, array(get_headers(), $body));
        }
        $message = unframed_post_json_message($body, $maxDepth);
    } catch (Exception $e) {
        http_response_code($e->getCode());
        return FALSE;
    }
    ignore_user_abort(true);
    unframed_cast_ok();
    call_user_func_array($fun, array($message));
    return TRUE;
}

interface UnframedCast {
    function POST (JSONMessage $message);
    function GET (JSONMessage $message);
}

function unframed_cast_control (UnframedCast $handler) {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'POST') {
        unframed_cast_json(array($handler, 'POST'));
    } elseif ($method == 'GET') {
        unframed_get_json(array($handler, 'GET'));
    } else {
        unframed_json_error(new Unframed('Invalid Method', 405));
    }
}

// semaphores, as PHP files touched and unlinked.

interface UnframedSemaphores {
    function down ($id);
    function up ($id);
    function isUp ($id);
    function time ($id);
    function connect ();
    function disconnect();
}

class UnframedSemaphoreFiles implements UnframedSemaphores {
    protected function file ($id) {
        return './.unframed_semaphore-'.$id;
    }
    function down ($id) {
        return @unlink($this->file($id));
    }
    function up ($id) {
        return touch($this->file($id));
    }
    function isUp ($id) {
        return !file_exists($this->file($id));
    }
    function time ($id) {
        return @filemtime($this->file($id));
    }
    function connect () {
        return TRUE;
    }
    function disconnect () {
        return TRUE;
    }
}

// tests & configure

class UnframedCastTest implements UnframedCast {
    private $semaphores;
    function __construct(UnframedSemaphores $semaphores) {
        $this->semaphores = $semaphores;
    }
    function configure ($timeout, $interval, $concurrent) {
        return unframed_invalidate_php('./unframed_cast_configuration.php', (
            "<"."?php\n"
            ."define('UNFRAMED_CAST_TIMEOUT', ".json_encode($timeout).");\n"
            ."define('UNFRAMED_CAST_INTERVAL', ".json_encode($interval).");\n"
            ."define('UNFRAMED_CAST_CONCURRENT', ".json_encode($concurrent).");\n"
        ));
    }
    final function POST (JSONMessage $message) {
        $sleep = $message->asFloat('sleep');
        sleep($sleep);
        if ($message->has('semaphore')) {
            $this->semaphores->down($message->getInt('semaphore'));
        } elseif ($message->has('configure')) {
            $configuration = $message->getMessage('configure');
            $this->configure(
                $configuration->getFloat('timeout', 0.005),
                $sleep,
                $configuration->getInt('concurrent', 8)
            );
        }
    }
    final function testTimeouts (JSONMessage $message) {
        $sleep = $message->asFloat('sleep', 2.0);
        $timeouts = $message->getList('timeouts', array(
            1, 2, 3, 4, 5, 6, 7, 8
        ));
        foreach($timeouts as $timeout) {
            $this->semaphores->up($timeout);
            unframed_cast(unframed_cast_url(), array(
                'sleep' => ($sleep-($timeout/1000)),
                'semaphore' => $timeout
                ), array(), $timeout/1000);
        }
        sleep($sleep + array_sum($timeouts)/1000 + 1);
        return array_filter($timeouts, array($this->semaphores, 'test'));
    }
    final function testSleeps ($sleeps, $timeout, $concurrent) {
        foreach ($sleeps as $sleep) {
            unframed_cast(unframed_cast_url(), array(
                'sleep' => $sleep,
                'configure' => array(
                    'timeout' => $timeout,
                    'concurrent' => $concurrent
                    ),
                ), array(), $timeout);
        }
    }
    function GET (JSONMessage $message) {
        $concurrents = $this->testTimeouts($message);
        $count = count($concurrents);
        if ($count > 0) {
            $timeout = (array_sum($concurrents) / $count) / 1000;
            $sleeps = $message->getList('sleeps', array(
                14, 29, 44, 59  // seconds
            ));
            $this->testSleeps($sleeps, $timeout, $count);
            return array(
                'pass' => TRUE,
                'concurrent' => $count,
                'timeout' => $timeout
            );
        } else {
            return array('pass' => FALSE);
        }
    }
}

function unframed_cast_test () {
    unframed_cast_control(new UnframedCastTest(new UnframedSemaphoreFiles()));
}

// functional legacy ...

function unframed_cast_test_get(JSONMessage $message) {
    $controller = new UnframedCastTest(new UnframedSemaphoreFiles());
    return $controller->GET($message);
}

function unframed_cast_test_json($control=NULL) {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'POST') {
        $controller = new UnframedCastTest(new UnframedSemaphoreFiles());
        unframed_cast_json(array($controller, 'POST'));
    } elseif ($control !== NULL && $method == 'GET') {
        unframed_get_json($control);
    } else {
        unframed_json_error(new Unframed('Invalid Method', 405));
    }
}