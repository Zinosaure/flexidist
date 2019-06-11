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
    const SCHEMA_VALUE_MISMATCH_SET_NULL = true;

    const SCHEMA_VALIDATE_IS_CONTENT = 'is_content';
    const SCHEMA_VALIDATE_IS_STRING = 'is_string';
    const SCHEMA_VALIDATE_IS_NUMERIC = 'is_numeric';
    const SCHEMA_VALIDATE_IS_INT = 'is_int';
    const SCHEMA_VALIDATE_IS_INTEGER = 'is_int';
    const SCHEMA_VALIDATE_IS_BOOLEAN = 'is_bool';
    const SCHEMA_VALIDATE_IS_LIST = 'is_array';
    const SCHEMA_VALIDATE_IS_OBJECT = 'is_object';
    const SCHEMA_VALIDATE_IS_SCHEMA = 'is_schema';
    const SCHEMA_VALIDATE_IS_LIST_OF = 'is_list_of:';
    const SCHEMA_VALIDATE_IS_OBJECT_OF = 'is_object_of:';

    const SCHEMA_VALIDATE_ATTRIBUTES = [];
    const SCHEMA_PRIMARY_KEY = null;

    protected $__attributes = [];
    protected $__schema_attributes = self::SCHEMA_VALIDATE_ATTRIBUTES;

    /**
    *
    */
    public function __construct(array $data = [], array $static_schema_attributes = []) {
        $this->__schema_attributes = array_replace_recursive(static::SCHEMA_VALIDATE_ATTRIBUTES, $static_schema_attributes);

        foreach ($this->__schema_attributes as $field => $is) {
            if (is_array($is))
                $this->__attributes[$field] = new Type\JSON($data[$field] ?? [], $is);
            else if ($is === self::SCHEMA_VALIDATE_IS_CONTENT 
                && (is_string($value = $data[$field] ?? null) || is_numeric($value) || is_callable([$value, '__toString'])))
                $this->__attributes[$field] = $value;
            else if ($is === self::SCHEMA_VALIDATE_IS_STRING && is_string($value = $data[$field] ?? null))
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
            else if (strpos($is, self::SCHEMA_VALIDATE_IS_LIST_OF) !== false && $class_name = str_replace(self::SCHEMA_VALIDATE_IS_LIST_OF, null, $is))
                $this->__attributes[$field] = self::createListOf($class_name, is_array($data ?? null) ? $data : []);
            else if (strpos($is, self::SCHEMA_VALIDATE_IS_OBJECT_OF) !== false 
                && class_exists($class_name = str_replace(self::SCHEMA_VALIDATE_IS_OBJECT_OF, null, $is)) && $value = new $class_name($data[$field] ?? []))
                $this->__attributes[$field] = $value;
            else 
                $this->__attributes[$field] = null;
        }
    }

    /**
    *
    */
    public function __toString() {
        $export_data = [];

        foreach ($this->__schema_attributes as $name => $is) {
            if (!in_array($is, [
                self::SCHEMA_VALIDATE_IS_CONTENT, 
                self::SCHEMA_VALIDATE_IS_STRING, 
                self::SCHEMA_VALIDATE_IS_NUMERIC, 
                self::SCHEMA_VALIDATE_IS_INT, 
                self::SCHEMA_VALIDATE_IS_INTEGER, 
                self::SCHEMA_VALIDATE_IS_BOOLEAN
            ])) {
                if ($this->{$name} instanceOf self)
                    $export_data[$name] = $this->{$name}->__attributes;
                else 
                    $export_data[$name] = (string) $this->{$name};
            } else
                $export_data[$name] = $this->{$name};
        }
        return json_encode($export_data, JSON_NUMERIC_CHECK);
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
        if (static::SCHEMA_VALUE_IS_READONLY || !$is = $this->__schema_attributes[$field] ?? null)
            return;
            
        if (is_array($is))
            $this->__attributes[$field] = new Type\JSON($data[$field] ?? [], $is);
        else if ($is === self::SCHEMA_VALIDATE_IS_CONTENT 
            && (is_string($value = $mixed_value ?? null) || is_numeric($value) || is_callable([$value, '__toString'])))
            $this->__attributes[$field] = $value;
        else if ($is === self::SCHEMA_VALIDATE_IS_STRING && is_string($value = $mixed_value ?? null))
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
        else if (strpos($is, self::SCHEMA_VALIDATE_IS_LIST_OF) !== false && $class_name = str_replace(self::SCHEMA_VALIDATE_IS_LIST_OF, null, $is))
            $this->__attributes[$field] = self::createListOf($class_name, is_array($mixed_value ?? null) ? $mixed_value : []);
        else if (strpos($is, self::SCHEMA_VALIDATE_IS_OBJECT_OF) !== false 
            && class_exists($class_name = str_replace(self::SCHEMA_VALIDATE_IS_OBJECT_OF, null, $is)) && $value = new $class_name($mixed_value ?? []))
            $this->__attributes[$field] = $value;
        else if (static::SCHEMA_VALUE_MISMATCH_SET_NULL)
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

    /**
    *
    */
    final public static function createListOf(string $class_name, array $data): object {

        /**
        *
        */

        return new class($class_name, $data) implements \ArrayAccess {
            
            /**
            *
            */
            protected $class_name = null;
            protected $data = [];

            /**
            *
            */
            public function __construct(string $class_name, array $data) {
                $this->class_name = $class_name;
                
                foreach ($data as $i => $Schema)
                    $this->offsetSet($i, $Schema);
            }

            /**
            *
            */
            public function __get($offset) {
                return $this->offsetGet($offset);
            }

            /**
            *
            */
            public function __set($offset, $Schema) {
                $this->offsetSet($offset, $Schema);
            }

            /**
            *
            */
            public function __isset($offset) {
                $this->offsetExists($offset);
            }

            /**
            *
            */
            public function __unset($offset) {
                $this->offsetUnset($offset);
            }
            
            /**
            *
            */
            public function offsetGet($offset) {
                return $this->data[$offset] ?? null;
            }

            /**
            *
            */
            public function offsetSet($offset, $Schema) {
                if ($Schema instanceOf $this->class_name || (is_array($Schema) && $Schema = new $this->class_name($Schema))) {
                    $offset = $Schema->ID() ?: $offset;

                    if (is_null($offset))
                        return $this->data[] = $Schema;
                    
                    $this->data[$offset] = $Schema;
                }
            }

            /**
            *
            */
            public function offsetExists($offset) {
                return isset($this->data[$offset]);
            }

            /**
            *
            */
            public function offsetUnset($offset) {
                if (is_object($offset) && $offset instanceOf $this->class_name)
                    $offset = array_search($offset, $this->data);

                unset($this->data[$offset]);
            }

            /**
            *
            */
            public function items(): array {
                return $this->data;
            }

            /**
            *
            */
            public function count(): int {
                return count($this->data);
            }
        };
    } 
}
?>