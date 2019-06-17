<?php

/**
*
*/
class Schema {

    /**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = false;
    const SCHEMA_FIELD_MISMATCH_SET_NULL = true;

    const SCHEMA_FIELD_IS_FILE = 'is_file';
    const SCHEMA_FIELD_IS_DIRECTORY = 'is_dir';
    const SCHEMA_FIELD_IS_CONTENT = 'is_content';
    const SCHEMA_FIELD_IS_STRING = 'is_string';
    const SCHEMA_FIELD_IS_NUMERIC = 'is_numeric';
    const SCHEMA_FIELD_IS_INT = 'is_int';
    const SCHEMA_FIELD_IS_INTEGER = 'is_int';
    const SCHEMA_FIELD_IS_BOOL = 'is_bool';
    const SCHEMA_FIELD_IS_BOOLEAN = 'is_bool';
    const SCHEMA_FIELD_IS_OBJECT = 'is_object';
    const SCHEMA_FIELD_IS_LIST = 'is_array';
    const SCHEMA_FIELD_IS_LIST_OF = 'is_array_of:';
    const SCHEMA_FIELD_IS_INSTANCE_OF = 'is_instance_of:';
    const SCHEMA_FIELD_IS_SCHEMA = 'is_schema:';

    const SCHEMA_FIELDS = [];

    protected $__values = [];
    protected $__schema_fields = self::SCHEMA_FIELDS;

    /**
    *
    */
    public function __construct($data = [], array $schema_fields = []) {
        if (is_string($data) && ($json_decode = json_decode($data, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $data = $json_decode;

        if ($data instanceOf self)
            $data = $data->__values;

        if (!is_array($data))
            $data = [];

        foreach ($schema_fields ?: static::SCHEMA_FIELDS as $field => $field_type) {
            if ((preg_match('/\[\]$/', $field) && $field = preg_replace('/(\[\])$/', null, $field))) {
                if (is_array($field_type))
                    $this->__schema_fields[$field] = self::SCHEMA_FIELD_IS_LIST_OF . self::SCHEMA_FIELD_IS_SCHEMA . json_encode($field_type);
                else
                    $this->__schema_fields[$field] = self::SCHEMA_FIELD_IS_LIST_OF . $field_type;
            } else if (is_array($field_type))
                $this->__schema_fields[$field] = self::SCHEMA_FIELD_IS_SCHEMA . json_encode($field_type);
            else
                $this->__schema_fields[$field] = $field_type;


            $this->{$field} = $data[$field] ?? null;
        }
    }

    /**
    *
    */
    final public function __isset(string $field): bool {
        if (isset($this->__values[$field]))
            return true;
        
        return false;
    }
    
    /**
    *
    */
    final public function &__get(string $field) {
        if (is_array($list = $this->__values[$field]))
            return $list;
        
        return $this->__values[$field];
    }

    /**
    *
    */
    public function __set(string $field, $mixed_value) {
        if ((static::SCHEMA_FIELD_IS_READONLY && isset($this->__values[$field]))|| !$field_type = $this->__schema_fields[$field] ?? null)
            return;
            
        if (strpos($field_type, $search = self::SCHEMA_FIELD_IS_LIST_OF . self::SCHEMA_FIELD_IS_SCHEMA) !== false 
                && ($schema_fields = str_replace($search, null, $field_type)) && is_array($mixed_value = $mixed_value ?? [])) {
            $this->__values[$field] = array_map(function($value) use ($schema_fields) {
                return new self($value, json_decode($schema_fields, JSON_OBJECT_AS_ARRAY));
            }, $mixed_value ?? []);
        } else if (strpos($field_type, $search = self::SCHEMA_FIELD_IS_SCHEMA) !== false 
                && ($schema_fields = str_replace($search, null, $field_type)) && is_array($mixed_value = $mixed_value ?? [])) {
            $this->__values[$field] = new self($mixed_value, json_decode($schema_fields, JSON_OBJECT_AS_ARRAY));
        } else if (strpos($field_type, $search = self::SCHEMA_FIELD_IS_LIST_OF . self::SCHEMA_FIELD_IS_INSTANCE_OF) !== false 
                && ($classname = str_replace($search, null, $field_type)) && is_array($mixed_value)) {
            $this->__values[$field] = array_map(function($value) use ($classname) {
                return class_exists($classname) && ($value instanceOf $classname || (is_array($value = $value ?? []) && ($value = new $classname($value)))) ? $value : null;
            }, $mixed_value ?? []);
        } else if (strpos($field_type, $search = self::SCHEMA_FIELD_IS_INSTANCE_OF) !== false 
                && $classname = str_replace($search, null, $field_type)) {
            if ($mixed_value instanceOf $classname || (is_array($mixed_value = $mixed_value ?? []) && ($mixed_value = new $classname($mixed_value))))
                $this->__values[$field] = $mixed_value;
        } else if (strpos($field_type, $search = self::SCHEMA_FIELD_IS_LIST_OF) !== false 
                && ($typeof = str_replace($search, null, $field_type)) && is_array($mixed_value = $mixed_value ?? [])) {
            $this->__values[$field] = array_map(function($value) use ($typeof) {
                return is_callable($typeof) && $typeof($value) ? $value : null;
            }, $mixed_value ?? []);
        } else if ($field_type === self::SCHEMA_FIELD_IS_LIST && is_array($value = $mixed_value ?? []))
            $this->__values[$field] = $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_FILE && file_exists($value = $mixed_value ?? null) && is_file($value))
            $this->__values[$field] = $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_DIRECTORY && file_exists($value = $mixed_value ?? null) && is_dir($value))
            $this->__values[$field] = $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_CONTENT
                && (is_string($value = $mixed_value ?? null) || is_numeric($value) || is_callable([$value, '__toString'])))
            $this->__values[$field] = file_exists($value) && is_file($value) ? file_get_contents($value) : $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_STRING && is_string($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_NUMERIC && is_numeric($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if (($field_type === self::SCHEMA_FIELD_IS_INTEGER || $field_type === self::SCHEMA_FIELD_IS_INT) && is_int($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if (($field_type === self::SCHEMA_FIELD_IS_BOOLEAN ||$field_type === self::SCHEMA_FIELD_IS_BOOL) && is_bool($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if ($field_type === self::SCHEMA_FIELD_IS_OBJECT && is_object($value = $mixed_value ?? (object) []))
            $this->__values[$field] = $mixed_value;
        else if (static::SCHEMA_FIELD_MISMATCH_SET_NULL || !isset($this->__values[$field]))
            $this->__values[$field] = null; 
    }

    /**
    *
    */
    public function __toString() {
        return json_encode($this->serialize(), JSON_NUMERIC_CHECK);
    }

    /**
    *
    */
    public function serialize(): array {
        $export_data = [];

        foreach ($this->__values as $field => $value) {
            if ($value instanceOf self)
                $export_data[$field] = $value->serialize();
            else if (is_object($value))
                $export_data[$field] = (string) $value;
            else if (is_array($value))
                $export_data[$field] = array_map(function($temp_value) {
                    if ($temp_value instanceOf self)
                        return $temp_value->serialize();
                    else if (is_object($temp_value))
                        return (string) $temp_value;
                    else
                        return $temp_value;
                }, $value);
            else  
                $export_data[$field] = $value;
        }

        return $export_data;
    }
}
?>