<?php

/**
*
*/
class Schema {

    /**
    *
    */
    const FIELD_SET_READONLY = false;
    const FIELD_SET_NULL_ON_TYPE_MISMATCH = true;

    const FIELD_IS_STRING = 'is_string';
    const FIELD_IS_NUMERIC = 'is_numeric';
    const FIELD_IS_INT = 'is_int';
    const FIELD_IS_INTEGER = 'is_int';
    const FIELD_IS_FLOAT = 'is_float';
    const FIELD_IS_DOUBLE = 'is_double';
    const FIELD_IS_BOOL = 'is_bool';
    const FIELD_IS_BOOLEAN = 'is_bool';
    const FIELD_IS_CONTENT = 'is_content';
    const FIELD_IS_ARRAY = 'is_array';
    const FIELD_IS_LIST = 'is_array';
    const FIELD_IS_OBJECT = 'is_object';
    const FIELD_IS_INSTANCE_OF = 'is_instance_of:';
    const FIELD_IS_LIST_OF = 'is_array_of:';
    const FIELD_IS_SCHEMA = 'is_schema:';

    const COLUMN_IS_AUTOINCREMENT = '|AUTOINCREMENT';
    const COLUMN_IS_PRIMARY_KEY = '|PRIMARY KEY';
    const COLUMN_IS_REQUIRED = '|NOT NULL';
    const COLUMN_IS_UNIQUE = '|UNIQUE';
    const COLUMN_HAVE_DEFAULT = '|DEFAULT ';
    const COLUMN_HAVE_CHECKED = '|CHECK ';

    const TABLE_NAME = null;
    const SCHEMA_FIELDS = [];

    private $__values = [];
    private $__field_constraints = [];

    /**
    *
    */
    public function __construct($data = [], $schema_fields = []) {
        $data = $this->__toarray($data);
        $schema_fields = $this->__toarray($schema_fields);
        
        foreach($schema_fields ?: static::SCHEMA_FIELDS as $field => $field_type) {
            if (preg_match('/\[\]$/', $field) && $field = preg_replace('/(\[\])$/', null, $field)) {
                if (is_array($field_type))
                    $this->__field_constraints[$field] = self::FIELD_IS_LIST_OF . self::FIELD_IS_SCHEMA . json_encode($field_type);
                else
                    $this->__field_constraints[$field] = self::FIELD_IS_LIST_OF . $field_type;
            }  else if (is_array($field_type))
                $this->__field_constraints[$field] = self::FIELD_IS_SCHEMA . json_encode($field_type);
            else
                $this->__field_constraints[$field] = $field_type;

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
    public function __set(string $field, $mixed_value) {
        if ((static::FIELD_SET_READONLY && array_key_exists($field, $this->__values)) || !$field_type = $this->__field_constraints[$field] ?? null)
            return;

        $this->__values[$field] = $this->__ismatched(explode('|', $field_type)[0], $field, $mixed_value);
    }

    /**
    *
    */
    private function __toarray($data): array {
        if ($data instanceOf self)
            $data = $data->__values;
        else if (is_string($data) && ($json_decode = json_decode($data, JSON_OBJECT_AS_ARRAY)) && json_last_error() === JSON_ERROR_NONE)
            $data = $json_decode;

        if (!is_array($data))
            $data = [];

        return $data;
    }

    /**
    *
    */
    private function __ismatched(string $typeof, string $field, $mixed_value) {
        if (!$typeof)
            return $value;

        if (strpos($typeof, $search = self::FIELD_IS_LIST_OF) !== false) {
            $typeof = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $typeof);
            $data = [];

            if (!is_array($mixed_value))
                return $data;

            foreach ($mixed_value as $value)
                $data[] = $this->__ismatched($typeof, $field, $value);

            return $data;
        } else if (strpos($typeof, $search = self::FIELD_IS_INSTANCE_OF) !== false) {
            $classname = preg_replace('/^' . preg_quote($search, '/') . '/is', null, $typeof);

            if ($mixed_value instanceOf $classname || (is_array($mixed_value) && ($mixed_value = new $classname($mixed_value))))
                return $mixed_value;
        } else if (strpos($typeof, $search = self::FIELD_IS_SCHEMA) !== false)
            return new self($mixed_value, preg_replace('/^' . preg_quote($search, '/') . '/is', null, $typeof));
        else if ($typeof == self::FIELD_IS_STRING && is_string($mixed_value))
            return $mixed_value;
        else if ($typeof == self::FIELD_IS_NUMERIC && is_string($mixed_value))
            return $mixed_value;
        else if ($typeof === self::FIELD_IS_CONTENT && (is_string($mixed_value) || is_numeric($mixed_value) || is_callable([$mixed_value, '__toString'])))
            return $mixed_value;
        else if (in_array($typeof, [self::FIELD_IS_INT, self::FIELD_IS_INTEGER]) && is_int($mixed_value))
            return $mixed_value;
        else if (in_array($typeof, [self::FIELD_IS_FLOAT, self::FIELD_IS_DOUBLE]) && is_float($mixed_value))
            return $mixed_value;
        else if (in_array($typeof, [self::FIELD_IS_BOOL, self::FIELD_IS_BOOLEAN]) && is_bool($mixed_value))
            return $mixed_value;
        else if (in_array($typeof, [self::FIELD_IS_LIST, self::FIELD_IS_ARRAY]) && is_array($mixed_value))
            return $mixed_value;
        else if ($typeof == self::FIELD_IS_OBJECT && is_object($mixed_value))
            return $mixed_value;

        return static::FIELD_SET_NULL_ON_TYPE_MISMATCH ? null : $this->__values[$field];
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
            '__field_constraints' => $this->__field_constraints,
        ];
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

    /**
    *
    */
    final public function createTableStructure() {
        $columns = [];
        
        foreach ($this->__field_constraints as $field => $field_type) {
            $constraints = explode('|', $field_type);
            $datatype = 'BLOB';

            if (in_array($field_type = array_shift($constraints), [self::FIELD_IS_INT, self::FIELD_IS_INTEGER]))
                $datatype = 'INTEGER';
            else if (in_array($field_type, [self::FIELD_IS_FLOAT, self::FIELD_IS_DOUBLE]))
                $datatype = 'REAL';
            else if (in_array($field_type, [self::FIELD_IS_NUMERIC, self::FIELD_IS_BOOL, self::FIELD_IS_BOOLEAN]))
                $datatype = 'NUMERIC';
            else if (in_array($field_type, [self::FIELD_IS_STRING, self::FIELD_IS_CONTENT]))
                $datatype = 'TEXT';
                
            $columns[$field_type] = sprintf('`%s` %s %s', $field, $datatype, implode(' ', $constraints) ?: 'NULL');
        }

        $query_string = sprintf('CREATE TABLE IF NOT EXISTS `%s` (%s);', static::TABLE_NAME ?: strtolower(basename(get_called_class())), implode(', ', $columns));

        return $query_string;
    }
}
?>