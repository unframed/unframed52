<?php

require '../../src/loop_json.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') { // Send cast message

    function unframed_loop_test ($message) {
        $command = $message->getString('command', 'status');
        if ($command == 'start') {
            return array('start' => unframed_loop_start(
                array('countdown' => $message->asInt('countdown', 10))
                ));
        } elseif ($command == 'stop') {
            return array('stop' => unframed_loop_stop());
        } elseif ($command == 'status') {
            return array('status' => unframed_loop_status());
        } else {
            throw new Unframed('Unknown command '.$command);
        }
    }
    unframed_get_json ('unframed_loop_test');

} elseif ($method == 'POST') { // Receive cast message

    function unframed_loop_test ($message) {
        $countdown = $message->getInt('countdown', 0) - 1;
        if ($countdown < 0) {
            return NULL;
        }
        return array('countdown' => $countdown);
    }
    unframed_loop_json('unframed_loop_test', 3);

} else { // 405

    unframed_json_error(new Unframed('Invalid Method', 405));

}
