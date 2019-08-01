<?php

/**
*
*/
class Schema {

    /**
    *
    */
    const SCHEMA_FIELD_SET_READONLY = false;
    const SCHEMA_FIELD_SET_NULL_ON_TYPE_MISMATCH = true;

    const SCHEMA_FIELD_IS_STRING = 'is_string';
    const SCHEMA_FIELD_IS_NUMERIC = 'is_numeric';
    const SCHEMA_FIELD_IS_INT = 'is_int';
    const SCHEMA_FIELD_IS_INTEGER = 'is_int';
    const SCHEMA_FIELD_IS_FLOAT = 'is_float';
    const SCHEMA_FIELD_IS_DOUBLE = 'is_double';
    const SCHEMA_FIELD_IS_BOOL = 'is_bool';
    const SCHEMA_FIELD_IS_BOOLEAN = 'is_bool';
    const SCHEMA_FIELD_IS_CONTENT = 'is_content';
    const SCHEMA_FIELD_IS_ARRAY = 'is_array';
    const SCHEMA_FIELD_IS_LIST = 'is_array';
    const SCHEMA_FIELD_IS_OBJECT = 'is_object';
    const SCHEMA_FIELD_IS_INSTANCE_OF = 'is_instance_of:';
    const SCHEMA_FIELD_IS_LIST_OF = 'is_array_of:';
    const SCHEMA_FIELD_IS_SCHEMA = 'is_schema:';

    const SCHEMA_DEFINITIONS = [];

    private $__values = [];
    private $__definitions = [];

    /**
    *
    */
    public function __construct($data = [], $schema_definitions = []) {
        if ($data instanceOf self)
            $data = $data->__values;
        else if (is_string($data) && ($json_decode = json_decode($data, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $data = $json_decode;

        $data = is_array($data) ? $data : [];

        if ($schema_definitions instanceOf self)
            $schema_definitions = $schema_definitions->__definitions;
        else if (is_string($schema_definitions) && ($json_decode = json_decode($schema_definitions, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $schema_definitions = $json_decode;

        $schema_definitions = is_array($schema_definitions) ? $schema_definitions : [];

        foreach ($schema_definitions ?: static::SCHEMA_DEFINITIONS as $attr_name => $attr_type) {
            $default_value = null;

            if (is_array($attr_type) && $default_value = new self([], $attr_type))
                $attr_type = self::SCHEMA_FIELD_IS_SCHEMA . json_encode($attr_type);
            
            if (preg_match('/\{\}/', $attr_name) && ($attr_name = preg_replace('/(\{\})/', null, $attr_name)) && !$default_value = null)
                $attr_type = self::SCHEMA_FIELD_IS_INSTANCE_OF . $attr_type;

            if (preg_match('/\[\]/', $attr_name) && ($attr_name = preg_replace('/(\[\])/', null, $attr_name)) && !$default_value = [])
                $attr_type = self::SCHEMA_FIELD_IS_LIST_OF . $attr_type;

            $this->__definitions[$attr_name] = $attr_type;
            $this->__values[$attr_name] = $this->__setField(explode('|', $attr_type)[0], $attr_name, $data[$attr_name] ?? null) ?? $default_value;
        }
    }

    /**
    *
    */
    public function __toString(): string {
        return json_encode($this->__exportValues(), JSON_NUMERIC_CHECK);
    }

    /**
    *
    */
    final public function __unset(string $attr_name) {}

    /**
    *
    */
    final public function &__get(string $attr_name) {
        $null = null;

        if (array_key_exists($attr_name, $this->__values)) {
            if (is_array($list = $this->__values[$attr_name]))
                return $list;
                
            return $this->__values[$attr_name];
        }

        $trace = debug_backtrace();
        trigger_error(sprintf('Undefined property via __get(): %s in %s from class %s', $attr_name, $trace[0]['file'], get_called_class()), E_USER_NOTICE);

        return $null;
    }

    /**
    *
    */
    final public function __set(string $attr_name, $mixed_value) {
        if (static::SCHEMA_FIELD_SET_READONLY || !$attr_type = $this->__definitions[$attr_name] ?? null)
            return;

        $this->__values[$attr_name] = $this->__setField(explode('|', $attr_type)[0], $attr_name, $mixed_value);
    }

    /**
    *
    */
    private function __setField(string $type_of, string $attr_name, $mixed_value) {
        if (!$type_of)
            return $mixed_value;

        if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_LIST_OF) !== false) {
            $type_of = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of);
            $data = [];

            if ($mixed_value instanceOf self)
                $mixed_value = $mixed_value->__values;
            else if (is_string($mixed_value) && ($json_decode = json_decode($mixed_value, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
                $mixed_value = $json_decode;

            foreach (is_array($mixed_value) ? $mixed_value : [] as $value)
                $data[] = $this->__setField($type_of, $attr_name, $value);

            return $data;
        } else if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_INSTANCE_OF) !== false) {
            $classname = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of);

            if ($mixed_value instanceOf $classname || (is_array($mixed_value) && ($mixed_value = new $classname(... array_values($mixed_value)))))
                return $mixed_value;
            else if (is_string($mixed_value) && (($mixed_value = json_decode($schema_definitions, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE) && $mixed_value = new $classname(... array_values($mixed_value)))
                return $mixed_value;
        } else if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_SCHEMA) !== false)
            return new self($mixed_value, preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of));
        else if (is_null($mixed_value))
            return null;
        else if ($type_of == self::SCHEMA_FIELD_IS_STRING && (is_string($mixed_value) || is_numeric($mixed_value)))
            return $mixed_value;
        else if ($type_of == self::SCHEMA_FIELD_IS_CONTENT && (is_string($mixed_value) || is_numeric($mixed_value) || is_callable([$mixed_value, '__toString'])))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_BOOL, self::SCHEMA_FIELD_IS_BOOLEAN]) && is_bool($mixed_value))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_LIST, self::SCHEMA_FIELD_IS_ARRAY]) && is_array($mixed_value))
            return $mixed_value;
        else if ($type_of == self::SCHEMA_FIELD_IS_OBJECT && is_object($mixed_value))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_INT, self::SCHEMA_FIELD_IS_INTEGER, self::SCHEMA_FIELD_IS_NUMERIC]) && is_numeric($mixed_value))
            return (int) $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_FLOAT, self::SCHEMA_FIELD_IS_DOUBLE]) && is_float((float) $mixed_value))
            return (float) $mixed_value;

        if (static::SCHEMA_FIELD_SET_NULL_ON_TYPE_MISMATCH)
            return null;
            
        return $this->__values[$attr_name] ?? null;
    }

    /**
    *
    */
    final public function __getValues(): array {
        return $this->__values;
    }

    /**
    *
    */
    final public function __getDefinitions(): array {
        return $this->__definitions;
    }

    /**
    *
    */
    final public function __importPropertiesOf(self $self, bool $merge_all = false) {
        foreach ($self->__exportProperties() as $attr_name => $value)
            $this->{$attr_name} = $merge_all ? array_replace_recursive($this->{$attr_name}, $value) : $value;
    }

    /**
    *
    */
    final public function __exportProperties(): array {
        return [
            '__values' => $this->__values,
            '__definitions' => $this->__definitions,
        ];
    }

    /**
    *
    */
    final public function __importValues(array $data) {
        foreach ($data as $attr_name => $value)
            if (array_key_exists($attr_name, $this->__definitions))
                $this->__values[$attr_name] = $value;
    }
    
    /**
    *
    */
    final public function __exportValues(): array {
        $export_values = [];

        foreach ($this->__values as $attr_name => $value) {
            if ($value instanceOf self)
                $export_values[$attr_name] = $value->__exportValues();
            else if (is_object($value) && is_callable([$value, '__toString']))
                $export_values[$attr_name] = (string) $value;
            else if (is_array($value))
                $export_values[$attr_name] = array_map(function($temp_value) {
                    if ($temp_value instanceOf self)
                        return $temp_value->__exportValues();
                    else if (is_object($temp_value) && is_callable([$value, '__toString']))
                        return (string) $temp_value;
                    else
                        return $temp_value;
                }, $value);
            else  
                $export_values[$attr_name] = $value;
        }

        return $export_values;
    }
}
?>