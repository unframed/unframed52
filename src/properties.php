<?

require_once(dirname(__FILE__).'/Unframed.php');

/**
 * A convenience to get typed properties from an array, a default or fail.
 */
class UnframedProperties {
    function __construct($array) {
        $this->array = $array;
    }
    function getDefault($key, $default=NULL) {
        $value = $this->array[$key];
        if (isset($value)) {
            return $value;
        }
        if ($default===NULL) {
            throw new Unframed('Name Error - $key missing');
        }
        return $default;
    }
    function setDefault($key, $default) {
        $value = $this->array[$key];
        if (isset($value)) {
            return $value;
        }
        $this->array[$key] = $default;
        return $default;
    }
    function getString($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_string($value)) {
            throw new Unframed('Type Error - $key must be a String');
        }
        return $value;
    }
    function getInt($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_int($value)) {
            throw new Unframed('Type Error - $key must be an Integer');
        }
        return $value;
    }
    function getFloat($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_float($value)) {
            throw new Unframed('Type Error - $key must be an Float');
        }
        return $value;
    }
    function getBool($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_bool($value)) {
            throw new Unframed('Type Error - $key must be an Boolean');
        }
        return $value;
    }
    function getArray($key, $default=NULL) {
        $value = $this->getDefault($key, $default);
        if (!is_array($value)) {
            throw new Unframed('Type Error - $key must be an Array');
        }
        return $value;
    }
    function asInt($key, $default=NULL) {
        return intval($this->getString($key, strval($default)));
    }
    function asFloat($key, $default=NULL) {
        return floatval($this->getString($key, strval($default)));
    }
    function asBool($key, $default=NULL) {
        return $this->getString($key, strval($default)) == 'true';
    }
}

/**
 * A properties factory.
 */
function unframed_properties($array) {
    if (!is_array($array)) {
        throw new Unframed('Type Error - '.var_export($array).' is not an array');
    }
    return new UnframedProperties($array);
}