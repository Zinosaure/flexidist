<?php

/**
*
*/
namespace LocalStorage;

/**
*
*/
abstract class Schema {

    /**
    *
    */
    const SCHEMA_VALIDATE_STRING            = 'is_string';
    const SCHEMA_VALIDATE_NUMERIC           = 'is_numeric';
    const SCHEMA_VALIDATE_INT               = 'is_int';
    const SCHEMA_VALIDATE_INTEGER           = 'is_int';
    const SCHEMA_VALIDATE_BOOLEAN           = 'is_bool';
    const SCHEMA_VALIDATE_LIST              = 'is_array';

    const VALIDATE_SCHEMA                   = [];

    /**
    *
    */
    final public function __construct(array $data = [], array $validate_schema = []) {
        foreach ($this->bind($data, $validate_schema) as $field => $value)
            $this->{$field} = $value;
    }

    /**
    *
    */
    final public function bind(array $data = [], array $validate_schema = []): array {
        $data_validated = [];

        foreach (array_replace($validate_schema = $validate_schema ?: static::VALIDATE_SCHEMA, $data) as $field => $value) {
            if (isset($validate_schema[$field]) 
                && ($sField = $validate_schema[$field])
                && (is_array($sField) || is_callable($sField))
            ) {
                if (is_array($sField))
                    $data_validated[$field] = $this->bind($data[$field] ?? [], $sField);
                else if (isset($data[$field]))
                    $data_validated[$field] = $sField($data[$field]) ? $data[$field] : null;
                else 
                    $data_validated[$field] = $sField == 'is_array' ? [] : null;
            } else if (isset($validate_schema[$field]))
                $data_validated[$field] = null;
        }

        return $data_validated;
    }

    /**
    *
    */
    final public static function getSchemaValidateType() {
        return 'd';
    }
}
?>