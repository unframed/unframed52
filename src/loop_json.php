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

function unframed_loop_json (JSONMessage $message, UnframedSemaphores $semaphores, $id) {
    $now = microtime(TRUE);
    $uris = $message->getList('uris');
    $interval = $message->asFloat('interval');
    $count = $message->asInt('count', 0) + 1;
    $time = $semaphores->time($id);
    if (!((time() - $time) < $interval)) {
        $semaphores->up($id);
        $state = array(
            'time' => $now,
            'interval' => $interval,
            'count' => $count
        );
        unframed_cast_all($uris, $state);
        $semaphores->disconnect();
        sleep($interval);
        $semaphores->connect();
        if ($semaphores->isUp($id)) {
            $message->map['count'] = $count;
            if (!unframed_cast_encoded(
                unframed_cast_url(), $message->encode(), '', UNFRAMED_LOOP_TIMEOUT
                )) {
                $semaphores->down($id);
            }
        }
    }
}

class UnframedLoop implements UnframedCast {
    protected $id;
    protected $uris;
    protected $semaphores;
    function __construct($uris, UnframedSemaphores $semaphores=NULL, $id=0) {
        $this->uris = $uris;
        $this->semaphores = (
            $semaphores === NULL ? new UnframedSemaphoreFiles() : $semaphores
        );
        $this->id = $id;
    }
    final function semaphoreIsUp () {
        return !$this->semaphores->isUp($this->id);
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
    function POST (JSONMessage $message) {
        unframed_loop_json ($message, $this->semaphores, $this->id);
    }
    final function start ($message) {
        if ($this->semaphoreIsUp()) {
            return 0;
        }
        return unframed_cast(unframed_cast_url(), array(
            'uris' => $this->uris,
            'interval' => $message->asFloat('interval', UNFRAMED_CAST_INTERVAL)
        ));
    }
    final function stop ($message) {
        if (!$this->semaphoreIsUp()) {
            return 0;
        }
        return $this->semaphoreDown();
    }
    final function status ($message) {
        return $this->semaphoreTime();
    }
    function GET (JSONMessage $message) {
        return unframed_loop_control($this, $message);
    }
}

function unframed_loop_control (UnframedLoop $loop, JSONMessage $message) {
    switch ($message->getString('command', 'status')) {
        case 'start':
            return array(
                'start' => $loop->start($message)
            );
        case 'stop':
            return array(
                'stop' => $loop->stop($message)
            );
        case 'status':
            return array(
                'status' => $loop->status($message)
            );
    }
    throw new Unframed('Unknown command');
}