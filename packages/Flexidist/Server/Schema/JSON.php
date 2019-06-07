<?php

/**
*
*/
namespace Flexidist\Server\Schema;

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

            if (class_exists($sField = $validate_schema[$field]))
                $this->{$field} = ($object = new $sField((array) ($data[$field] ?? []))) instanceOf self ? $object : null;
            else if ($sField == self::SCHEMA_VALIDATE_LIST)
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
        
        $callback = static::VALIDATE_SCHEMA[$name];
        $attribute = $this->vars[$name] ?? null;
        
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
    final public static function create(array $data = [], array $validate_schema = []): self {
        return new static($data, $validate_schema);
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
    final public function exportIndexes($ID, string $filename): array {
        $export_data = [];

        foreach (static::SCHEMA_INDEX_KEYS as $i => $name) {
            if ($this->{$name} instanceOf self)
                $export_data[$name] = $this->{$name}->exportIndexes($ID, $filename);
            else
                $export_data[$name][$this->{$name}][$ID] = $filename;
        }

        return $export_data;
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