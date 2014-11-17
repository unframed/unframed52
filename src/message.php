<?php

require_once(dirname(__FILE__).'/Unframed.php');

unframed_no_script(__FILE__);

/**
 * A convenience to get typed properties from an associative array, a default or fail.
 */
class UnframedMessage {
    public $array;
    function __construct($array) {
        $this->array = $array;
    }
    /**
     * Get the values of the wrapped associative array as a list
     */
    function values () {
        return array_values($this->array);
    }
    /**
     *
     */
    function has ($key) {
        return array_key_exists($key, $this->array);
    }
    /**
     * Get the value of $key in $this->array if it is set, or a
     * $default not NULL, or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return any $this->array[$key] or $default
     * @throws Unframed exception with a name error
     */
    function getDefault($key, $default=NULL) {
        if (array_key_exists($key, $this->array)) {
            return $this->array[$key];
        }
        if ($default===NULL) {
            throw new Unframed('Name Error - '.$key.' missing');
        }
        return $default;
    }
    /**
     * Set the value of $key in $this->array to $default if it was not set yet,
     * return the (maybe updated) value of $this->array[$key] if it is a string
     * or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return any $this->array[$key] or $default
     */
    function setDefault($key, $default) {
        if (array_key_exists($key, $this->array)) {
            return $this->array[$key];
        }
        $this->array[$key] = $default;
        return $default;
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * assert that it is a string or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return string $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getString($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_string($value)) {
            throw new Unframed('Type Error - '.$key.' must be a String');
        }
        return $value;
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * assert that it is an integer or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return int $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getInt($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_int($value)) {
            throw new Unframed('Type Error - '.$key.' must be an Integer');
        }
        return $value;
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * assert that it is a float or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return float $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getFloat($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_float($value)) {
            throw new Unframed('Type Error - '.$key.' must be an Float');
        }
        return $value;
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * assert that it is an boolean or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return bool $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getBool($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_bool($value)) {
            throw new Unframed('Type Error - '.$key.' must be an Boolean');
        }
        return $value;
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * assert that it is an array or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return array $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getArray($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_array($value)) {
            throw new Unframed('Type Error - '.$key.' must be an Array');
        }
        return $value;
    }
    /**
     * Get the value of $key or a $default not NULL, assert that it is an list or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return array $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getList($key, $default=NULL) {
        $value = $this->getArray($key, $default);
        if (!unframed_is_list($value)) {
            throw new Unframed('Type Error - '.$key.' must be a List');
        }
        return $value;
    }
    function getListAsFloat($key, $default=NULL) {
        return array_map('floatval', $this->getList($key, $default));
    }
    function getListAsInt($key, $default=NULL) {
        return array_map('intval', $this->getList($key, $default));
    }
    /**
     * Get the value of $key or a $default not NULL, assert that it is a map or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return array $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getMap($key, $default=NULL) {
        $value = $this->getArray($key, $default);
        if (!unframed_is_map($value)) {
            throw new Unframed('Type Error - '.$key.' must be a Map');
        }
        return $value;
    }
    /**
     * Get a new UnframedMessage boxing the array value of $key
     * or a $default not NULL.
     *
     * @param string $key
     * @param any $default
     *
     * @return UnframedMessage boxing $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function getMessage($key, $default=NULL) {
        return new UnframedMessage($this->getMap($key, $default));
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * as a string, or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return string $this->array[$key] or $default
     * @throws Unframed exception with a name error
     */
    function asString($key, $default=NULL) {
        return strval($this->getDefault($key, $default));
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * as an integer, or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return int $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function asInt($key, $default=NULL) {
        return intval($this->asString($key, $default));
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * as a float, or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return int $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function asFloat($key, $default=NULL) {
        return floatval($this->asString($key, $default));
    }
    /**
     * Get the value of $key in $this->array or a $default not NULL,
     * as a boolean, or fail.
     *
     * @param string $key
     * @param any $default
     *
     * @return int $this->array[$key] or $default
     * @throws Unframed exception with a name or type error
     */
    function asBool($key, $default=NULL) {
        return $this->asString($key, $default) == 'true';
    }
}

/**
 * An UnframedMessage factory.
 */
function unframed_message($array) {
    if (!is_array($array)) {
        throw new Unframed('Type Error - '.var_export($array).' is not an array');
    }
    return new UnframedMessage($array);
}