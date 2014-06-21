<?

require_once(dirname(__FILE__).'/Unframed.php');

/**
 * A convenience to get typed properties from an associative array, a default or fail.
 */
class UnframedMessage {
    function __construct($array) {
        $this->array = $array;
    }
    /**
     * Get the values of the wrapped associative array as a list 
     */
    function asList () {
        return array_values($this->array);
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
        return intval($this->getString($key, strval($default)));
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
        return floatval($this->getString($key, strval($default)));
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
        return $this->getString($key, strval($default)) == 'true';
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