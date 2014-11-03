<?php

require_once(dirname(__FILE__).'/post_json.php');

unframed_no_script(__FILE__);

// How to cast a JSON message to a relative URL in PHP 5.2

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
 * @param in $timeout of the request, defaults to UNFRAMED_CAST_TIMEOUT
 *
 * @return boolean
 */
function unframed_cast_encoded ($url, $content, $timeout=UNFRAMED_CAST_TIMEOUT) {
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
 * @param string $json JSON object to POST
 * @param in $timeout of the request, defaults to UNFRAMED_CAST_TIMEOUT
 *
 * @return boolean
 */
function unframed_cast ($url, $json, $timeout=UNFRAMED_CAST_TIMEOUT) {
    return unframed_cast_encoded($url, json_encode($json), $timeout);
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
 * Try to return the $json message decoded from a request body submitted
 * by a local address, or fail.
 *
 * @param int $maxLength of the JSON body accepted, defaults to 16384
 * @param int $maxDepth of the JSON message accepted, defaults to 512
 * @param int $options JSON options, default to 0
 *
 * @return UnframedMessage
 * @throws Unframed
 */
function unframed_cast_receive ($maxLength=16384, $maxDepth=512, $options=0) {
    $remote = $_SERVER['REMOTE_ADDR'];
    if (!($remote == '127.0.0.1' || $remote == $_SERVER['SERVER_ADDR'])) {
        throw new Unframed('Unauthorized '.$_SERVER['REMOTE_ADDR'], 403);
    }
    return unframed_post_json_body($maxLength, $maxDepth, $options);
}

/**
 * Handle a POSTed JSON request with $fun ... after a response is sent.
 *
 * @param callable $fun to handle the POSTed JSON request
 * @param int $maxLength of the JSON request body, defaults to 16384 bytes
 * @param in $maxDepth of the JSON request, defaults to 512
 *
 * @throws Unframed
 */
function unframed_cast_json ($fun, $maxLength=16384, $maxDepth=512) {
    try {
        $message = unframed_cast_receive($maxLength, $maxDepth);
    } catch (Unframed $e) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
        return FALSE;
    }
    unframed_cast_ok();
    unframed_call($fun, array($message));
    return TRUE;
}

// tests

function unframed_cast_test_post ($message) {
    // sleep ...
    $sleep = $message->asFloat('sleep');
    sleep($sleep);
    // ... then either ...
    if ($message->has('semaphore')) {
        // ... unlink a semaphore.
        $semaphore = $message->getString('semaphore');
        unlink($semaphore);
    } elseif ($message->has('configure')) {
        // or (re)configure.
        $configuration = $message->getMessage('configure');
        unframed_configure (
            $configuration->asInt('concurrent', 8),
            $configuration->asFloat('timeout', 0.03),
            $sleep
            );
    }
}

function unframed_cast_test_semaphore ($slug, $index) {
    return dirname(__FILE__).'/.unframed_cast_test_'.$slug.'-'.$index;
}

function unframed_cast_test_timeout ($ms) {
    return !file_exists(unframed_cast_test_semaphore('timeout', $ms));
}

function unframed_cast_test_timeouts ($sleep, $timeouts) {
    foreach($timeouts as $timeout) {
        $semaphore = unframed_cast_test_semaphore('timeout', $timeout);
        touch($semaphore);
        unframed_cast(unframed_cast_url(), array(
            'sleep' => ($sleep-($timeout/1000)),
            'semaphore' => $semaphore
            ), $timeout/1000);
    }
    sleep($sleep + array_sum($timeouts)/1000 + 1);
    return array_filter($timeouts, 'unframed_cast_test_timeout');
}

function unframed_cast_test_sleeps ($sleeps, $timeout, $concurrent) {
    foreach ($sleeps as $sleep) {
        unframed_cast(unframed_cast_url(), array(
            'sleep' => $sleep,
            'configure' => array(
                'concurrent' => $concurrent,
                'timeout' => $timeout
                ),
            ), $timeout);
    }
}

/**
 * Test various timeouts and maximum execution time.
 */
function unframed_cast_test_get ($message) {
    $sleep = $message->asFloat('sleep', 2.0);
    $timeouts = array(
        1, 2, 3, 4, 5, 6, 7, 8
        ); // in ms
    $concurrents = unframed_cast_test_timeouts($sleep, $timeouts);
    $concurrency = count($concurrents);
    if ($concurrency > 0) {
        $timeout = (array_sum($concurrents) / $concurrency) / 1000;
        $sleeps = array(
            14, 29, 44, 59
            ); // in seconds
        unframed_cast_test_sleeps(
            $sleeps, $timeout, $concurrency
            );
        return array(
            'pass' => TRUE,
            'concurrent' => $concurrency,
            'timeout' => $timeout
            );
    } else {
        return array(
            'pass' => FALSE
            );
    }
}

/**
 * The unframed_cast_test application.
 */
function unframed_cast_test_json ($fun='unframed_cast_test_get') {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'GET') { // Send cast messages
        unframed_get_json ($fun);
    } elseif ($method == 'POST') { // Receive cast messages
        unframed_cast_json('unframed_cast_test_post');
    } else {
        unframed_json_error(new Unframed('Invalid Method', 405));
    }
}