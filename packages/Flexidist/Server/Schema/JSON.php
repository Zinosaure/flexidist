<?php

/**
*
*/
namespace Flexidist\Server\Schema;

/**
*
*/

interface ListOf {}

/**
*
*/
abstract class JSON {

    /**
    *
    */
    const SCHEMA_VALIDATE_STRING = 'is_string';
    const SCHEMA_VALIDATE_NUMERIC = 'is_numeric';
    const SCHEMA_VALIDATE_INT = 'is_int';
    const SCHEMA_VALIDATE_INTEGER = 'is_int';
    const SCHEMA_VALIDATE_BOOLEAN = 'is_bool';
    const SCHEMA_VALIDATE_LIST = 'is_array';
    const SCHEMA_VALIDATE_LIST_OF = 'a_list_of:';

    const VALIDATE_SCHEMA = [];
    const SCHEMA_PRIMARY_KEY = null;
    const SCHEMA_INDEX_KEYS = [];

    protected $vars = [];

    /**
    *
    */
    final public function __construct(array $data = [], array $validate_schema = []) {
        foreach (array_replace($validate_schema = $validate_schema ?: static::VALIDATE_SCHEMA, $data) as $field => $value) {
            if (!isset($validate_schema[$field]))
                continue;

            if (class_exists($sField = $validate_schema[$field]) && $object = new $sField((array) ($data[$field] ?? [])))
                $this->{$field} = $object instanceOf self ? $object : null;
            else if (strpos($sField, self::SCHEMA_VALIDATE_LIST_OF) !== false && isset($data[$field])) {
                $this->{$field} = is_array($data[$field]) ? $data[$field] : [];
            } else if ($sField == self::SCHEMA_VALIDATE_LIST)
                $this->{$field} = array_values($data[$field] ?? []);
            else
                $this->{$field} = $data[$field] ?? null; 
        }
    }

    /**
    *
    */
    final public function __get(string $name) {
        return $this->vars[$name];
    }
    
    /**
    *
    */
    final public function __set(string $name, $mixed_value) {
        if (!isset(static::VALIDATE_SCHEMA[$name]))
            return;
        
        $sField = static::VALIDATE_SCHEMA[$name];
        $attribute = $this->vars[$name] ?? null;
        
        if (strpos($sField, self::SCHEMA_VALIDATE_LIST_OF) !== false && ($instance_of = str_replace(self::SCHEMA_VALIDATE_LIST_OF, null, $sField))) {
            $this->vars[$name] = new class($instance_of, $mixed_value) implements ListOf {
            
                protected $instance_of = null;
                protected $data = [];
    
                public function __construct(string $instance_of, $data) {
                    $this->instance_of = $instance_of;
    
                    foreach ((array) $data as $i => $mixed_value)
                        $this->set($i, $mixed_value);
                }
    
                public function items(): array {
                    return $this->data;
                }
    
                public function item($index) {
                    return $this->data[$index] ?? null;
                }
    
                public function set($index, $mixed_value) {
                    if ((is_array($mixed_value) && ($object = new $this->instance_of($mixed_value))) || ($object = $mixed_value) instanceOf $this->instance_of) {
                        if (!is_null($index))
                            $this->data[$index] = $object;
                        else
                            $this->data[] = $object;
                    }
                }
    
                public function unset($index) {
                    if (is_object($index))
                        $index = array_search($index, $this->data);
    
                    unset($this->data[$index]);
                }
            };
        } else if (is_null($attribute) 
            && !is_null($mixed_value) 
            && (
                (is_callable($sField) && $sField($mixed_value))
                || $mixed_value instanceOf $sField
            )
        )
            $this->vars[$name] = $mixed_value;
        else if (!is_null($attribute) 
            && !is_null($mixed_value) 
            && (
                (is_callable($sField) && $sField($mixed_value))
                || (is_object($attribute) && is_a($mixed_value, get_class($attribute)))
            )
        )
            $this->vars[$name] = $mixed_value;
        else 
            $this->vars[$name] = null;
    }
    
    /**
    *
    */
    final public function __toString(): string {
        return json_encode($this->export(), JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
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
    final public function export(): array {
        $export_data = [];

        foreach ($this->vars as $field => $value)
            $export_data[$field] = ($value instanceOf self) ? $value->export() : $value;

        return $export_data;
    }
}
?>