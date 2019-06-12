<?php

/**
*
*/
namespace Schema\Type;

/**
*
*/
class JSON extends \Schema\Schema {

    /**
    *
    */
    public function __construct($data, array $static_schema_attributes = []) {
        if (is_string($data))
            $data = (array) @json_decode($data, JSON_OBJECT_AS_ARRAY);

        if (is_array($data))
            parent::__construct($data, $static_schema_attributes);
    }

    /**
    *
    */
    public function __toString() {
        return json_encode($this->__attributes, JSON_NUMERIC_CHECK);
    }

    /**
    *
    */
    public function jsonLastError(string $json_string): ?string {
        $result = json_decode($json_string);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return null;
            case JSON_ERROR_DEPTH:
                return 'The maximum stack depth has been exceeded.';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Invalid or malformed JSON.';
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded.';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON.';
            // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                return 'One or more recursive references in the value to be encoded.';
            // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                return 'One or more NAN or INF values in the value to be encoded.';
            case JSON_ERROR_UNSUPPORTED_TYPE:
                return 'A value of a type that cannot be encoded was given.';
            default:
                return 'Unknown JSON error occured.';
        }
    }
}
?>