<?php

require_once(dirname(__FILE__).'/cast_json.php');

/**
 * Handle a POSTed JSON request with $fun ... after a response is sent.
 *
 * @param callable $fun to handle the POSTed JSON request
 * @param int $maxLength of the JSON request body, defaults to 16384 bytes
 * @param in $maxDepth of the JSON request, defaults to 512
 */
function unframed_fold_json($fun, $maxLength=16384, $maxDepth=512) {
    try {
        $messages = unframed_cast_receive($maxLength, $maxDepth);
    } catch (Unframed $error) {
        http_response_code($e->getCode());
        echo $e->getMessage(), "\n";
    }
    if (isset($messages)) {
        $message = array_pop($messages);
        if (!empty($messages)) {
            unframed_cast(unframed_cast_url(), $messages);
        }
        unframed_cast_ok();
        unframed_call($fun, array($message, $messages));
    }
}
