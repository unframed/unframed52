<?php

/**
 * Che va piano va sano.
 *
 * A slow loop to periodically start concurrent scripts and provide
 * each enough timing information to run each occurence in the loop's
 * interval.
 */

require_once(dirname(__FILE__).'/cast_json.php');

unframed_no_script(__FILE__);

function unframed_loop_semaphore () {
    return dirname(__FILE__).'/.loop-'.md5($_SERVER['SCRIPT_FILENAME']);
}

/**
 * Cast the same $json array to all $uris and return the encoded JSON.
 */
function unframed_loop_cast_all ($uris, $json) {
    $content = json_encode($json);
    foreach ($uris as $uri) {
        unframed_cast_encoded(unframed_cast_url($uri), $content);
    }
    return $content;
}

/**
 * Cast the loop's state to all $uris, every $seconds, guarded by $semaphore.
 *
 * Note that the cast will silently fail and this instance of the loop will stop
 * if the semaphore was touched sooner than $seconds before now, ie: if another
 * instance of the loop is running.
 *
 * @param array $uris to cast
 * @param float $seconds of interval between each loop's run
 * @param string $semaphore the name of the semaphore file guard for this loop
 */
function unframed_loop_json ($uris, $seconds, $semaphore) {
    try {
        $json = unframed_cast_receive();
        $time = $json->asFloat('time', (microtime(TRUE) - $seconds));
        $interval = $json->asFloat('interval', $seconds);
        $count = $json->asInt('count', 0);
    } catch (Unframed $e) {
        http_response_code($e->getCode());
        echo $e->getMessage();
        echo "\n";
        return FALSE;
    }
    unframed_cast_ok();
    if (!(
        file_exists($semaphore) &&
        ((time() - filemtime($semaphore)) < $interval)
        )) {
        touch($semaphore);
        $now = microtime(TRUE);
        $state = array(
            'time' => $now,
            'interval' => $seconds,
            'count' => ($count + 1)
            );
        $encoded = unframed_loop_cast_all($uris, $state);
        sleep($seconds);
        if (file_exists($semaphore)) {
            unframed_cast_encoded(unframed_cast_url(), $encoded);
        }
    }
    return TRUE;
}

/**
 * Start a loop by casting a $message to its $uri, using the $semaphore as guard.
 *
 * @param array $message to send
 * @param string $uri to cast, default to NULL and the current script's own URI
 * @param string $semaphore filename, defaults to '.unframed_loop'
 *
 * @return TRUE on success, FALSE otherwise
 */
function unframed_loop_start ($uri, $semaphore) {
    if (!file_exists($semaphore)) {
        return unframed_cast_encoded(unframed_cast_url($uri), '{}');
    }
    return FALSE;
}

/**
 * Stop a loop by deleting its $semaphore.
 *
 * @param string $semaphore filename, defaults to '.unframed_loop'
 *
 * @return TRUE on success, FALSE otherwise
 */
function unframed_loop_stop ($semaphore) {
    if (file_exists($semaphore)) {
        unlink($semaphore);
        return TRUE;
    }
    return FALSE;
}

/**
 * Get the last time a loop ran by stating its $semaphore.
 *
 * @param string $semaphore filename, defaults to '.unframed_loop'
 *
 * @return FALSE if the loop is not running
 */
function unframed_loop_status ($semaphore) {
    return filemtime($semaphore);
}

class UnframedLoopControl {
    public $uri;
    public $semaphore;
    public function call ($message) {
        $command = $message->getString('command', 'status');
        if ($command == 'start') {
            return array(
                'start' => unframed_loop_start($this->uri, $this->semaphore)
                );
        } elseif ($command == 'stop') {
            return array(
                'stop' => unframed_loop_stop($this->semaphore)
                );
        } elseif ($command == 'status') {
            return array(
                'status' => unframed_loop_status($this->semaphore)
                );
        } else {
            throw new Unframed('Unknown command '.$command);
        }
    }
}

function unframed_loop_control($uri, $semaphore) {
    $control = new UnframedLoopControl();
    $control->uri = $uri;
    $control->semaphore = $semaphore;
    return array($control, 'call');
}

/**
 * Loop around and cast local URIs, provides a loop control API by default.
 *
 * ~~~php
 * require 'unframed52/loop_json.php';
 *
 * unframed_loop(array(
 *     'map.php',
 *     'queue.php',
 *     'reduce.php',
 *     'schedule.php'
 *     ));
 * ~~~
 *
 * Each URI will receive the same message:
 *
 * {
 *     "time": 1412579707.3825,
 *     "count": 1293,
 *     "interval": 30
 *     }
 */
function unframed_loop (
    $uris,
    $fun='unframed_loop_control',
    $seconds=UNFRAMED_LOOP_TIMEOUT
    ) {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'POST') {
        unframed_loop_json($uris, $seconds, unframed_loop_semaphore());
    } elseif ($fun !== NULL && $method == 'GET') {
        unframed_get_json(call_user_func_array(
            $fun, array(NULL, unframed_loop_semaphore())
            ));
    } else {
        unframed_json_error(new Unframed('Invalid Method', 405));
    }
}