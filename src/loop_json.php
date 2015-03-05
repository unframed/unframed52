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

if (!defined('UNFRAMED_LOOP_TIMEOUT')) {
    define('UNFRAMED_LOOP_TIMEOUT', 0.1);
}

class UnframedLoop implements UnframedCast {
    protected $id;
    protected $uris;
    protected $semaphores;
    final function __construct($id, $uris, UnframedSemaphores $semaphores) {
        $this->id = $id;
        $this->uris = $uris;
        $this->semaphores = $semaphores;
    }
    final function semaphoreTest () {
        return !$this->semaphores->test($this->id);
    }
    final function semaphoreUp () {
        return $this->semaphores->up($this->id);
    }
    final function semaphoreDown () {
        return $this->semaphores->down($this->id);
    }
    final function semaphoreTime () {
        return $this->semaphores->time($this->id);
    }
    final function POST (JSONMessage $message) {
        $now = microtime(TRUE);
        $uris = $message->getList('uris');
        $interval = $message->asFloat('interval');
        $count = $message->asInt('count', 0) + 1;
        $time = $this->semaphoreTime();
        if (!((time() - $time) < $interval)) {
            $this->semaphoreUp();
            $state = array(
                'time' => $now,
                'interval' => $interval,
                'count' => $count
                );
            unframed_cast_all($uris, $state);
            sleep($interval);
            if ($this->semaphoreTest()) {
                $message->map['count'] = $count;
                if (!unframed_cast_encoded(
                    unframed_cast_url(), $message->encode(), '', UNFRAMED_LOOP_TIMEOUT
                    )) {
                    $this->semaphoreDown();
                }
            }
        }
    }
    final function start ($message) {
        if ($this->semaphoreTest()) {
            return TRUE;
        }
        return unframed_cast(unframed_cast_url(), array(
            'uris' => $this->uris,
            'interval' => $message->asFloat('interval', UNFRAMED_CAST_INTERVAL)
        ));
    }
    final function stop ($message) {
        if (!$this->semaphoreTest()) {
            return TRUE;
        }
        return $this->semaphoreDown();
    }
    final function status ($message) {
        return $this->semaphoreTime();
    }
    function GET (JSONMessage $message) {
        switch ($message->getString('command', 'status')) {
            case 'start':
                return array(
                    'start' => $this->start($message)
                );
            case 'stop':
                return array(
                    'stop' => $this->stop($message)
                );
            case 'status':
                return array(
                    'status' => $this->status($message)
                );
        }
        throw new Unframed('Unknown command');
    }
}

function unframed_loop (array $uris, UnframedSemaphores $semaphores=NULL) {
    unframed_cast_control(new UnframedLoop(
        0, $uris, ($semaphores === NULL ? new UnframedSemaphoreFiles() : $semaphores)
    ));
}