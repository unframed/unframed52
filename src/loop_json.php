<?php

require_once(dirname(__FILE__).'/fold_json.php');

/**
 * Cast UNFRAMED_LOOP_MESSAGE to the loop's own URL 
 * if the semaphore file UNFRAMED_LOOP_CONTINUE exists. 
 */
function unframed_loop_continue () {
    if (file_exists(UNFRAMED_LOOP_CONTINUE)) {
        unframed_cast(unframed_cast_url(), UNFRAMED_LOOP_MESSAGE);
    }
}

/**
 * Fail or receive a JSON cast, if the result of $fun($json) is not NULL, sleep 
 * $seconds - 30 by default - and then either cast the result to itself or delete
 * the $semaphore to stop the loop.
 *
 * Note that the cast will silently fail and this instance of the loop will stop
 * if the semaphore was touched sooner than $seconds before now, ie: if another
 * instance of the loop is running.
 *
 * @param callable $fun to apply to update the loop's $state
 * @param int $seconds to sleep between two cast to the loop
 * @param string $semaphore the name of the semaphore file for this loop
 */
function unframed_loop_json ($fun, $seconds=30, $semaphore='.unframed_loop') {
    define('UNFRAMED_LOOP_CONTINUE', $semaphore);
    try {
        $json = unframed_cast_receive();
    } catch (Unframed $error) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
    }
    if (isset($json)) {
        unframed_cast_ok();
        if (!(file_exists($semaphore) && (time() - filemtime($semaphore) < $seconds))) {
            register_shutdown_function('unframed_loop_continue');
            touch($semaphore);
            $state = unframed_call($fun, array($json));
            if ($state == NULL) {
                unlink($semaphore);
            } else {
                define('UNFRAMED_LOOP_MESSAGE', $state);
                sleep($seconds);
            }
        }
    }
}

function unframed_loop_start ($message, $uri=NULL, $semaphore='.unframed_loop') {
    if (!file_exists($semaphore)) {
        touch($semaphore);
        unframed_cast(unframed_cast_url($uri), $message);
    }
}

function unframed_loop_stop ($semaphore='.unframed_loop') {
    if (file_exists($semaphore)) {
        unlink($semaphore);
    }
}