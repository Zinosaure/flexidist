<?php

/**
*
*/
namespace Schema;

/**
*
*/

class Schema {
    
    /**
    *
    */
    const SCHEMA_VALUE_IS_READONLY = false;

    const SCHEMA_VALIDATE_IS_STRING = 'is_string';
    const SCHEMA_VALIDATE_IS_NUMERIC = 'is_numeric';
    const SCHEMA_VALIDATE_IS_INT = 'is_int';
    const SCHEMA_VALIDATE_IS_INTEGER = 'is_int';
    const SCHEMA_VALIDATE_IS_BOOLEAN = 'is_bool';
    const SCHEMA_VALIDATE_IS_LIST = 'is_array';
    const SCHEMA_VALIDATE_IS_OBJECT = 'is_object';
    const SCHEMA_VALIDATE_IS_SCHEMA = 'is_schema';
    const SCHEMA_VALIDATE_IS_SCHEMA_DATA = 'is_schema_data';
    const SCHEMA_VALIDATE_IS_LIST_OF = 'is_list_of:';
    const SCHEMA_VALIDATE_IS_OBJECT_OF = 'is_object_of:';

    const SCHEMA_VALIDATE_ATTRIBUTES = [];
    const SCHEMA_PRIMARY_KEY = null;

    protected $__attributes = [];

    /**
    *
    */
    public function __construct(array $data = [], array $static_schema_attributes = []) {
        foreach ($static_schema_attributes ?: static::SCHEMA_VALIDATE_ATTRIBUTES as $field => $is) {
            if ($is === self::SCHEMA_VALIDATE_IS_STRING && is_string($value = $data[$field] ?? null))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_NUMERIC && is_numeric($value = $data[$field] ?? null))
                $this->__attributes[$field] = $value;
            else if (($is === self::SCHEMA_VALIDATE_IS_INTEGER || $is === self::SCHEMA_VALIDATE_IS_INT) && is_int($value = $data[$field] ?? null))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_BOOLEAN && is_bool($value = $data[$field] ?? null))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_LIST && is_array($value = $data[$field] ?? []))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_OBJECT && is_object($value = $data[$field] ?? (object) []))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_SCHEMA && is_object($value = $data[$field] ?? (object) []) && $value instanceOf self)
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_SCHEMA_DATA && is_array($value = $data[$field] ?? []))
                $this->__attributes[$field] = new self(... array_values($value));
            else if (strpos($is, self::SCHEMA_VALIDATE_IS_LIST_OF) !== false && $class_name = str_replace(self::SCHEMA_VALIDATE_IS_LIST_OF, null, $is))
                $this->__attributes[$field] = new ListOf($class_name, is_array($data[$field]) ? $data[$field] : []);
            else if (strpos($is, self::SCHEMA_VALIDATE_IS_OBJECT_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_VALIDATE_IS_OBJECT_OF, null, $is))
                && is_object($value = $data[$field] ?? (object) []) && $value instanceOf $class_name)
                $this->__attributes[$field] = $value;
            else
                $this->__attributes[$field] = null;
        }
    }

    /**
    *
    */
    final public function __get(string $field) {
        return $this->__attributes[$field];
    }
    
    /**
    *
    */
    final public function __set(string $field, $mixed_value) {
        if (static::SCHEMA_VALUE_IS_READONLY || !$is = static::SCHEMA_VALIDATE_ATTRIBUTES[$field] ?? null)
            return;
            
        if ($is === self::SCHEMA_VALIDATE_IS_STRING && is_string($value = $mixed_value ?? null))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_NUMERIC && is_numeric($value = $mixed_value ?? null))
            $this->__attributes[$field] = $value;
        else if (($is === self::SCHEMA_VALIDATE_IS_INTEGER || $is === self::SCHEMA_VALIDATE_IS_INT) && is_int($value = $mixed_value ?? null))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_BOOLEAN && is_bool($value = $mixed_value ?? null))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_LIST && is_array($value = $mixed_value ?? []))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_OBJECT && is_object($value = $mixed_value ?? (object) []))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_SCHEMA && is_object($value = $mixed_value ?? (object) []) && $value instanceOf self)
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_SCHEMA_DATA && is_array($value = $mixed_value ?? []))
            $this->__attributes[$field] = new self(... array_values($value));
        else if (strpos($is, self::SCHEMA_VALIDATE_IS_LIST_OF) !== false && $class_name = str_replace(self::SCHEMA_VALIDATE_IS_LIST_OF, null, $is))
            $this->__attributes[$field] = new ListOf($class_name, is_array($mixed_value) ? $mixed_value : []);
        else if (strpos($is, self::SCHEMA_VALIDATE_IS_OBJECT_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_VALIDATE_IS_OBJECT_OF, null, $is))
                && is_object($value = $mixed_value ?? (object) []) && $value instanceOf $class_name)
            $this->__attributes[$field] = $value;
        else
            $this->__attributes[$field] = null;
    }

    /**
    *
    */
    final public function ID() {
        if (static::SCHEMA_PRIMARY_KEY)
            return $this->{static::SCHEMA_PRIMARY_KEY};
        
        return null;
    }
}
?>