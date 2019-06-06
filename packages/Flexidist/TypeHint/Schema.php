<?php

/**
*
*/
namespace Flexidist\TypeHint;

/**
*
*/
abstract class Schema {

    /**
    *
    */
    const SCHEMA_VALIDATE_STRING = 'is_string';
    const SCHEMA_VALIDATE_NUMERIC = 'is_numeric';
    const SCHEMA_VALIDATE_INT = 'is_int';
    const SCHEMA_VALIDATE_INTEGER = 'is_int';
    const SCHEMA_VALIDATE_BOOLEAN = 'is_bool';
    const SCHEMA_VALIDATE_LIST = 'is_array';
    const VALIDATE_SCHEMA = [];

    protected $vars = [];

    /**
    *
    */
    final public function __construct(array $data = [], array $validate_schema = []) {
        foreach (array_replace($validate_schema = $validate_schema ?: static::VALIDATE_SCHEMA, $data) as $field => $value) {
            if (!isset($validate_schema[$field]))
                continue;

            if (class_exists($sField = $validate_schema[$field]))
                $this->vars[$field] = ($object = new $sField($data[$field] ?? [])) instanceOf self ? $object : null;
            else if ($sField == self::SCHEMA_VALIDATE_LIST)
                $this->vars[$field] = $data[$field] ?? [];
            else
                $this->vars[$field] = $data[$field] ?? null; 
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
        
        $callback = static::VALIDATE_SCHEMA[$name];
        $attribute = $this->vars[$name];
        
        if (is_null($attribute) 
            && !is_null($mixed_value) 
            && (
                (is_callable($callback) && $callback($mixed_value))
                || $mixed_value instanceOf $callback
            )
        )
            $this->vars[$name] = $mixed_value;
        else if (!is_null($attribute) 
            && !is_null($mixed_value) 
            && (
                (is_callable($callback) && $callback($mixed_value))
                || (is_object($attribute) && is_a($mixed_value, get_class($attribute)))
            )
        )
            $this->vars[$name] = $mixed_value;
    }
    
    /**
    *
    */
    final public function __toString(): string {
        return $this->stringify();
    }

    /**
    *
    */
    final public static function create(array $data = [], array $validate_schema = []): self {
        return new static($data, $validate_schema);
    }
    
    /**
    *
    */
    final public static function getSchemaValidateType($data) {
    	return new static($data);
    }
    
    /**
    *
    */
    public function stringify(): string {
        return json_encode($this->vars, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
    }
}
?>