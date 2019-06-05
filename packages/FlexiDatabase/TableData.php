<?php

namespace FlexiDatabase;

class TableData {

    const SCHEMA_VALIDATE_STRING            = 'is_string';
    const SCHEMA_VALIDATE_NUMERIC           = 'is_numeric';
    const SCHEMA_VALIDATE_INT               = 'is_int';
    const SCHEMA_VALIDATE_INTEGER           = 'is_int';
    const SCHEMA_VALIDATE_BOOLEAN           = 'is_bool';
    const SCHEMA_VALIDATE_LIST              = 'is_array';
    const SCHEMA_TABLE_SCHEMA               = [];

    protected $data = [];

    public function __construct(array $data, array $table_schema = []) {
        $this->data = array_map(function($value) {
            return $this->build(static::TABLE_SCHEMA, $value);
        }, $data);
    }

    public static function load(string $path_name, array $table_schema = []): self {
        return new static((array) json_decode(file_get_contents($path_name), JSON_OBJECT_AS_ARRAY), $table_schema);
    }

    protected function build(array $schema, array $data): DataSchema {
        $schema_data = [];

        foreach (array_replace($schema, $data) as $field => $value) {
            if (isset($schema[$field]) 
                && ($sField = $schema[$field])
                && (
                    is_array($sField) && is_array($value)
                        || is_callable($sField) && filter_var($value, FILTER_CALLBACK, ['options' => $sField])
                )
            ) {
                if (is_array($sField) && is_array($value))
                    $schema_data[$field] = $this->build($schema[$field], $value);
                else
                    $schema_data[$field] = $value;
            } else if (isset($schema[$field]))
                $schema_data[$field] = null;
        }

        return new DataSchema($schema_data);
    }

    public function insert(array $data): bool {

    }

    public function update(array $data): bool {

    }

    public function delete(array $data): bool {

    }

    public function count(): int {

    }

    public function commit(): bool {

    }

    public function save(): bool {

    }
}
?>