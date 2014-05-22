<?php

require_once(dirname(__FILE__).'/post_json.php');

// How to cast a JSON message to a relative URL in PHP 5.2

/**
 * Return the complete URL of the given $uri or the script's URL if
 * $uri is NULL.
 * 
 * @param string $uri to complete, NULL by default
 */
function unframed_cast_url($uri=NULL) {
    return (
        "http".(!empty($_SERVER['HTTPS'])?"s":"")."://"
        .$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
        .(parse_url($uri==NULL?$_SERVER['REQUEST_URI']:$uri)['path'])
        );
}

/**
 * POST a JSON $request to a $url with HTTP/1.0, return the response's body.
 *
 * @param string $url POSTed to
 * @param int $request JSON object to POST
 * @param in $timeout of the request, defaults to 0.05 seconds
 *
 * @return string the response's body
 */
function unframed_cast ($url, $request, $timeout=0.05) {
    $content = json_encode($request);
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
    @file_get_contents($url, false, $context);
    return True;
}

// How to handle a local cast message

/**
 * Send an HTTP 200 response with zero content, flush and continue ...
 */
function unframed_cast_ok () {
    http_response_code(200);
    header("Connection: close");
    header("Content-length: 0");
    flush();
}

/**
 * Try to return the $json message decoded from a request body submitted
 * by a local address, or fail.
 *
 * @param int $maxLength of the JSON body accepted, defaults to 16384
 * @param int $maxDepth of the JSON message accepted, defaults to 512
 *
 * @return array
 * @throws Unframed 
 */
function unframed_cast_receive ($maxLength=16384, $maxDepth=512) {
    $remote = $_SERVER['REMOTE_ADDR']; 
    if (!($remote == '127.0.0.1' || $remote == $_SERVER['SERVER_ADDR'])) {
        throw new Unframed('Unauthorized '.$_SERVER['REMOTE_ADDR'], 403);    
    }
    return unframed_post_json_body($maxLength, $maxDepth);    
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
        $json = unframed_cast_receive($maxLength, $maxDepth);
    } catch (Unframed $e) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
    }
    if (isset($json)) {
        unframed_cast_ok();
        unframed_call($fun, array($json));
    }
}
