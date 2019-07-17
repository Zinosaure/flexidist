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
        $data = $this->__jsonDecode($data);
        $schema_definitions = $this->__jsonDecode($schema_definitions);
        
        foreach($schema_definitions ?: static::SCHEMA_DEFINITIONS as $field => $field_type) {
            if (preg_match('/\[\]$/', $field) && $field = preg_replace('/(\[\])$/', null, $field)) {
                if (is_array($field_type))
                    $this->__definitions[$field] = self::SCHEMA_FIELD_IS_LIST_OF . self::SCHEMA_FIELD_IS_SCHEMA . json_encode($field_type);
                else
                    $this->__definitions[$field] = self::SCHEMA_FIELD_IS_LIST_OF . $field_type;
            }  else if (is_array($field_type))
                $this->__definitions[$field] = self::SCHEMA_FIELD_IS_SCHEMA . json_encode($field_type);
            else
                $this->__definitions[$field] = $field_type;

            $this->{$field} = $data[$field] ?? null;
        }
    }

    /**
    *
    */
    final public function __isset(string $field): bool {
        if (array_key_exists($field, $this->__values))
            return true;
        
        return false;
    }

    /**
    *
    */
    final public function __unset(string $field) {}
    
    /**
    *
    */
    final public function &__get(string $field) {
        $null = null;

        if (array_key_exists($field, $this->__values)) {
            if (is_array($list = $this->__values[$field]))
                return $list;
                
            return $this->__values[$field];
        }

        $trace = debug_backtrace();
        trigger_error(sprintf('Undefined property via __get(): %s in %s from class %s', $field, $trace[0]['file'], get_called_class()), E_USER_NOTICE);

        return $null;
    }

    /**
    *
    */
    final public function __set(string $field, $mixed_value) {
        if ((static::SCHEMA_FIELD_SET_READONLY && array_key_exists($field, $this->__values)) || !$field_type = $this->__definitions[$field] ?? null)
            return;

        $this->__values[$field] = $this->__setField(explode('|', $field_type)[0], $field, $mixed_value);
    }

    /**
    *
    */
    public function __toString() {
        return json_encode($this->__exportValues(), JSON_NUMERIC_CHECK);
    }

    /**
    *
    */
    final public function __values(): array {
        return $this->__values;
    }

    /**
    *
    */
    final public function __definitions(): array {
        return $this->__definitions;
    }

    /**
    *
    */
    private function __setField(string $type_of, string $field, $mixed_value) {
        if (!$type_of)
            return $value;

        if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_LIST_OF) !== false) {
            $type_of = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of);
            $data = [];

            foreach ($this->__jsonDecode($mixed_value) as $value)
                $data[] = $this->__setField($type_of, $field, $value);

            return $data;
        } else if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_INSTANCE_OF) !== false) {
            $classname = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of);

            if ($mixed_value instanceOf $classname || (is_array($mixed_value) && ($mixed_value = new $classname($mixed_value))))
                return $mixed_value;
        } else if (strpos($type_of, $search = self::SCHEMA_FIELD_IS_SCHEMA) !== false)
            return new self($mixed_value, preg_replace('/^' . preg_quote($search, '/') . '/is', null, $type_of));
        else if (is_null($mixed_value) || $mixed_value == '')
            return null;
        else if ($type_of == self::SCHEMA_FIELD_IS_STRING && (is_string($mixed_value) || is_int($mixed_value) || is_numeric($mixed_value)))
            return $mixed_value;
        else if ($type_of == self::SCHEMA_FIELD_IS_NUMERIC && is_numeric($mixed_value))
            return (int) $mixed_value;
        else if ($type_of == self::SCHEMA_FIELD_IS_CONTENT && (is_string($mixed_value) || is_numeric($mixed_value) || is_callable([$mixed_value, '__toString'])))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_INT, self::SCHEMA_FIELD_IS_INTEGER]) && is_int($mixed_value))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_FLOAT, self::SCHEMA_FIELD_IS_DOUBLE]) && is_float((float) $mixed_value))
            return (float) $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_BOOL, self::SCHEMA_FIELD_IS_BOOLEAN]) && is_bool($mixed_value))
            return $mixed_value;
        else if (in_array($type_of, [self::SCHEMA_FIELD_IS_LIST, self::SCHEMA_FIELD_IS_ARRAY]) && is_array($mixed_value))
            return $mixed_value;
        else if ($type_of == self::SCHEMA_FIELD_IS_OBJECT && is_object($mixed_value))
            return $mixed_value;

        if (static::SCHEMA_FIELD_SET_NULL_ON_TYPE_MISMATCH)
            return null;
            
        return $this->__values[$field] ?? null;
    }

    /**
    *
    */
    private function __jsonDecode($data): array {
        if ($data instanceOf self)
            $data = $data->__values;
        else if (is_string($data) && ($json_decode = json_decode($data, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $data = $json_decode;

        return is_array($data) ? $data : [];
    }

    /**
    *
    */
    final public function __importPropertiesOf(self $self, bool $merge_all = false) {
        foreach ($self->__exportProperties() as $field => $value)
            $this->{$field} = $merge_all ? array_replace_recursive($this->{$field}, $value) : $value;
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
        foreach ($data as $field => $value)
            $this->__values[$field] = $value;
    }
    
    /**
    *
    */
    final public function __exportValues(): array {
        $export_values = [];

        foreach ($this->__values as $field => $value) {
            if ($value instanceOf self)
                $export_values[$field] = $value->__exportValues();
            else if (is_object($value) && is_callable([$value, '__toString']))
                $export_values[$field] = (string) $value;
            else if (is_array($value))
                $export_values[$field] = array_map(function($temp_value) {
                    if ($temp_value instanceOf self)
                        return $temp_value->__exportValues();
                    else if (is_object($temp_value) && is_callable([$value, '__toString']))
                        return (string) $temp_value;
                    else
                        return $temp_value;
                }, $value);
            else  
                $export_values[$field] = $value;
        }

        return $export_values;
    }
}
?>