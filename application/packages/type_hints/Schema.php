<?php

/**
*
*/
namespace type_hints;

/**
*
*/

class Schema {
    
    /**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = false;
    const SCHEMA_FIELD_MISMATCH_SET_NULL = true;

    const SCHEMA_FIELD_IS_CONTENT = 'is_content';
    const SCHEMA_FIELD_IS_STRING = 'is_string';
    const SCHEMA_FIELD_IS_NUMERIC = 'is_numeric';
    const SCHEMA_FIELD_IS_INT = 'is_int';
    const SCHEMA_FIELD_IS_INTEGER = 'is_int';
    const SCHEMA_FIELD_IS_BOOLEAN = 'is_bool';
    const SCHEMA_FIELD_IS_LIST = 'is_array';
    const SCHEMA_FIELD_IS_OBJECT = 'is_object';
    const SCHEMA_FIELD_IS_SCHEMA = 'is_schema';
    const SCHEMA_FIELD_IS_LIST_OF = 'is_list_of:';
    const SCHEMA_FIELD_IS_OBJECT_OF = 'is_object_of:';

    const SCHEMA_FIELDS = [];

    protected $__values = [];
    protected $__schema_fields = self::SCHEMA_FIELDS;

    /**
    *
    */
    public function __construct($data = [], array $schema_fields = []) {
        $this->__schema_fields = array_replace_recursive(static::SCHEMA_FIELDS, $schema_fields);

        if (is_string($data) && ($json_decode = json_decode($data, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $data = $json_decode;

        if (!is_array($data))
            $data = [];

        foreach ($this->__schema_fields as $field => $is) {
            if (is_array($is) && is_array($temp_is = current($is))) {
                $this->__values[$field] = [];

                foreach ($data[$field] ?? [] as $temp_data)
                    $this->__values[$field][] = new self($temp_data, $temp_is);
            } else if (is_array($is))
                $this->__values[$field] = new self($data[$field] ?? [], $is);
            else if ($is === self::SCHEMA_FIELD_IS_CONTENT
                && (is_string($value = $data[$field] ?? null) || is_numeric($value) || is_callable([$value, '__toString'])))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_STRING && is_string($value = $data[$field] ?? null))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_NUMERIC && is_numeric($value = $data[$field] ?? null))
                $this->__values[$field] = $value;
            else if (($is === self::SCHEMA_FIELD_IS_INTEGER || $is === self::SCHEMA_FIELD_IS_INT) && is_int($value = $data[$field] ?? null))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_BOOLEAN && is_bool($value = $data[$field] ?? null))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_LIST && is_array($value = $data[$field] ?? []))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_OBJECT && is_object($value = $data[$field] ?? (object) []))
                $this->__values[$field] = $value;
            else if ($is === self::SCHEMA_FIELD_IS_SCHEMA && is_object($value = $data[$field] ?? (object) []) && $value instanceOf self)
                $this->__values[$field] = $value;
            else if (strpos($is, self::SCHEMA_FIELD_IS_LIST_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_FIELD_IS_LIST_OF, null, $is))) {
                $this->__values[$field] = [];

                foreach ($data[$field] ?? [] as $temp_data)
                    $this->__values[$field][] = new $class_name($temp_data);
            } else if (strpos($is, self::SCHEMA_FIELD_IS_OBJECT_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_FIELD_IS_OBJECT_OF, null, $is))) {
                if (is_object($value = $data[$field] ?? null) && $value instanceOf $class_name)
                    $this->__values[$field] = $value;
                else
                    $this->__values[$field] = new $class_name($value);
            } else
                $this->__values[$field] = null;
        }
    }

    /**
    *
    */
    final public function &__get(string $field) {
        return $this->__values[$field];
    }
    
    /**
    *
    */
    final public function __set(string $field, $mixed_value) {
        if (static::SCHEMA_FIELD_IS_READONLY || !$is = $this->__schema_fields[$field] ?? null)
            return;
            
        if (is_array($is) && is_array($temp_is = current($is))) {
            $this->__values[$field] = [];

            foreach ($data[$field] ?? [] as $temp_data)
                $this->__values[$field][] = new self($temp_data, $temp_is);
        } else if (is_array($is))
            $this->__values[$field] = new self($data[$field] ?? [], $is);
        else if ($is === self::SCHEMA_FIELD_IS_CONTENT
            && (is_string($value = $mixed_value ?? null) || is_numeric($value) || is_callable([$value, '__toString'])))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_STRING && is_string($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_NUMERIC && is_numeric($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if (($is === self::SCHEMA_FIELD_IS_INTEGER || $is === self::SCHEMA_FIELD_IS_INT) && is_int($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_BOOLEAN && is_bool($value = $mixed_value ?? null))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_LIST && is_array($value = $mixed_value ?? []))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_OBJECT && is_object($value = $mixed_value ?? (object) []))
            $this->__values[$field] = $value;
        else if ($is === self::SCHEMA_FIELD_IS_SCHEMA && is_object($value = $mixed_value ?? (object) []) && $value instanceOf self)
            $this->__values[$field] = $value;
        else if (strpos($is, self::SCHEMA_FIELD_IS_LIST_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_FIELD_IS_LIST_OF, null, $is))) {
            $this->__values[$field] = [];

            foreach ($data[$field] ?? [] as $temp_data)
                $this->__values[$field][] = new $class_name($temp_data);
        } else if (strpos($is, self::SCHEMA_FIELD_IS_OBJECT_OF) !== false && class_exists($class_name = str_replace(self::SCHEMA_FIELD_IS_OBJECT_OF, null, $is))) {
            if (is_object($value = $mixed_value ?? null) && $value instanceOf $class_name)
                $this->__values[$field] = $value;
            else
                $this->__values[$field] = new $class_name($value);
        } else if (static::SCHEMA_FIELD_MISMATCH_SET_NULL)
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

        foreach ($this->__schema_fields as $name => $is) {
            if (!in_array($is, [
                self::SCHEMA_FIELD_IS_CONTENT,
                self::SCHEMA_FIELD_IS_STRING,
                self::SCHEMA_FIELD_IS_NUMERIC,
                self::SCHEMA_FIELD_IS_INT,
                self::SCHEMA_FIELD_IS_INTEGER,
                self::SCHEMA_FIELD_IS_BOOLEAN
            ])) {
                if ($this->{$name} instanceOf self)
                    $export_data[$name] = $this->{$name}->serialize();
                else if (is_array($this->{$name}) && is_array($is) && is_array(current($is)))
                    foreach ($this->{$name} as $value)
                        $export_data[$name][] = $value->serialize();
                else
                    $export_data[$name] = (string) $this->{$name};
            } else
                $export_data[$name] = $this->{$name};
        }

        return $export_data;
    }
}
?>