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
            
            $sField = $validate_schema[$field];

            if (isset($data[$field]))
                $this->vars[$field] = $data[$field];
            else if (class_exists($sField))
                $this->vars[$field] = new $sField();
            else
                $this->vars[$field] = null; 
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
    final public static function bind(array $data = [], array $validate_schema = []): array {
        $data_validated = [];

        foreach (array_replace($validate_schema = $validate_schema ?: static::VALIDATE_SCHEMA, $data) as $field => $value) {
            if (isset($validate_schema[$field])
                && ($sField = $validate_schema[$field])
                && (is_array($sField) || is_callable($sField) || class_exists($sField))
            ) {
                if (is_array($sField))
                    $data_validated[$field] = self::bind($data[$field] ?? [], $sField);
                else if (isset($data[$field]) && is_callable($method = $sField . '::getSchemaValidateType') && ($object = $method($data[$field])) instanceOf self)
                    $data_validated[$field] = $object;
                else if (isset($data[$field]))
                    $data_validated[$field] = $sField($data[$field]) ? $data[$field] : null;
                else if ($sField == self::SCHEMA_VALIDATE_LIST)
                    $data_validated[$field] = [];
                else if (class_exists($sField))
                	$data_validated[$field] = new $sField();
                else
                	$data_validated[$field] = null;
            } else if (isset($validate_schema[$field]))
                $data_validated[$field] = null;
        }

        return $data_validated;
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