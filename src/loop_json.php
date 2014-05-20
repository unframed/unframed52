<?php

require_once(dirname(__FILE__).'/fold_json.php');

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
    try {
        $json = unframed_cast_receive();
    } catch (Unframed $e) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
    }
    if (isset($json)) {
        unframed_cast_ok();
        if (!(file_exists($semaphore) && (time() - filemtime($semaphore) < $seconds))) {
            // register_shutdown_function('unframed_loop_continue');
            touch($semaphore);
            $state = unframed_call($fun, array($json));
            if ($state == NULL) {
                unlink($semaphore);
            } else {
                sleep($seconds);
                if (file_exists($semaphore)) {
                    unframed_cast(unframed_cast_url(), $state);
                }
            }
        }
    }
}

function unframed_loop_start ($message, $uri=NULL, $semaphore='.unframed_loop') {
    if (!file_exists($semaphore)) {
        unframed_cast(unframed_cast_url($uri), $message);
        return TRUE;
    }
    return FALSE;
}

function unframed_loop_stop ($semaphore='.unframed_loop') {
    if (file_exists($semaphore)) {
        unlink($semaphore);
        return TRUE;
    }
    return FALSE;
}

function unframed_loop_status ($semaphore='.unframed_loop') {
    if (file_exists($semaphore)) {
        return filemtime($semaphore);
    }
    return 0;
}