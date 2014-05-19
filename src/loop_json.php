<?php

/**
 * Bend it like Joe.
 *
 *     unframed_loop($verify_key, $poll_messages, $cast_url);
 *
 * Receive a JSON message POSTed with HTTP/1.0.
 *
 * Verify a key with a function or fail.
 *
 * Poll messages from a function.
 *
 * Cast messages to as many folded processes for the same URL.
 *
 * Commit any results to a function or sleep a while if the queue is empty. 
 *
 * Then loop, cast to self with a new time and key.
 *
 * POST /loop.php { "time": 0, "processes": 4, "key": "..." }
 * POST /cast.php { "time": 0, "messages": [...] }
 *
 * Note that a cast does not require a key but a local origin.
 */

require_once(dirname(__FILE__).'/fold_json.php');

define('UNFRAMED_LOOP_TIME', microtime(TRUE));

/**
 * Call the loop's own URL if `UNFRAMED_LOOP_CONTINUE` is defined and true. 
 */
function unframed_loop_continue () {
    if (defined('UNFRAMED_LOOP_CONTINUE') && UNFRAMED_LOOP_CONTINUE) {
        unframed_cast(unframed_cast_url(), array(
            'time' => UNFRAMED_LOOP_TIME, 
            'connection' => connection_status()
            ));
    }
}

/**
 * Accept a JSON request POSTed, fail or handle it with $fun and if
 * the return value is not negative make sure script's own URL is called
 * before this instance terminates.
 */
function unframed_loop ($fun) {
    try {
        $json = unframed_cast_receive();
    } catch (Unframed $error) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
    }
    if (isset($json)) {
        register_shutdown_function('unframed_loop_continue');
        unframed_cast_ok();
        $interval = unframed_call($fun, array());
        if ($interval < 0) {
            define('UNFRAMED_LOOP_CONTINUE', FALSE);
        } else {
            define('UNFRAMED_LOOP_CONTINUE', TRUE);
            sleep($interval);
        }
    }
}
