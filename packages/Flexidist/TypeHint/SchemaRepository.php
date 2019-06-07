<?php

/**
*
*/
namespace Flexidist\TypeHint;

/**
*
*/
abstract class SchemaRepository {

    /**
    *
    */
    private $Schema = null;
    private $Schema_name = null;
    private $data_path = null;
    private $glob_pattern = '*.json';
    private $cache_data = [];

    private $metadata_filename = null;
    public static $metadata = [];

    /**
    *
    */
    final public function __construct(Schema $Schema, string $data_path = '.', string $glob_pattern = '*.json') {
        $this->Schema = $Schema;
        $this->Schema_name = get_called_class();
        $this->data_path = $data_path;
        $this->glob_pattern = $glob_pattern;
        self::$metadata[$this->Schema_name] = ['IDs' => [], 'indexation' => [], 'count_file' => 0];

        if ($is_indexed = file_exists($this->metadata_filename = sprintf('%s/_%s', $data_path, md5($this->Schema_name))))
            self::$metadata[$this->Schema_name] = json_decode(file_get_contents($this->metadata_filename), JSON_OBJECT_AS_ARRAY);
        
        $count_file = count(glob(sprintf('%s/%s', $data_path, $glob_pattern)));

        if (!$is_indexed || $count_file != self::$metadata[$this->Schema_name]['count_file'])
            $this->performIndexation();
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
	final public function performIndexation(): bool {
        foreach (glob(sprintf('%s/%s', $this->data_path, $this->glob_pattern)) as $filename)
            $this->append($filename);

        return (bool) file_put_contents($this->metadata_filename, json_encode(self::$metadata[$this->Schema_name], JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK));
    }

    /**
    *
    */
    final public function append(string $filename): bool {
        if (!file_exists($filename) || in_array($basename = basename($filename), self::$metadata[$this->Schema_name]['IDs']))
            return false;

        if ($this->Schema::SCHEMA_PRIMARY_KEY) {
            $Schema = $this->Schema::create((array) @json_decode(file_get_contents($filename), JSON_OBJECT_AS_ARRAY));
            self::$metadata[$this->Schema_name]['IDs'][$ID = $Schema->ID()] = $basename;
            
            if ($this->Schema::SCHEMA_INDEX_KEYS) {
                foreach ($this->Schema::SCHEMA_INDEX_KEYS as $name) {
                    if (!isset($this->Schema::VALIDATE_SCHEMA[$name]))
                        continue;
        
                    if ($Schema->{$name} instanceOf Schema)
                        self::$metadata[$this->Schema_name]['indexation'][$name][$ID] = $Schema->{$name}->exportIndexation();
                    else
                        self::$metadata[$this->Schema_name]['indexation'][$name][$ID] = $Schema->{$name};
                }
            }
        } else
            self::$metadata[$this->Schema_name]['IDs'][] = $basename;

        self::$metadata[$this->Schema_name]['count_file'] ++;

        return true;
    }

    /**
    *
    */
    final public function update(string $index_name, $ID, $mixed_value): bool {
        if (!isset(self::$metadata[$this->Schema_name]['indexation'][$index_name], self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID]))
            return false;

        $callback = $this->Schema::VALIDATE_SCHEMA[$index_name];
        $attribute = self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID] ?? null;

        if (is_null($mixed_value) 
            || (class_exists($callback) && ($Schema = new $callback()) instanceOf Schema && is_a($mixed_value, get_class($Schema)) && $mixed_value = $mixed_value->exportIndexation())
            || (is_callable($callback) && $callback($mixed_value)))
            return (bool) self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID] = $mixed_value;
        
        return false;
    }

    /**
    *
    */
    final public function delete($ID): bool {
        if (!isset(self::$metadata[$this->Schema_name]['IDs'][$ID]))
            return false;

        foreach (self::$metadata[$this->Schema_name]['indexation'] as $index_name => $value) {
            if (isset(self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID]))
                unset(self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID]);
        }

        unset(self::$metadata[$this->Schema_name]['IDs'][$ID]);
        self::$metadata[$this->Schema_name]['count_file'] = count(self::$metadata[$this->Schema_name]['IDs']);

        return true;
    }

    /**
    *
    */
    final public function search(string $name) {
        $return_value = $this->data;

        if (empty($name))
            return null;

        foreach (explode('.', strtolower($name)) as $key) {
            if (is_array($return_value) && array_key_exists($key, $return_value))
                $return_value = $return_value[$key];
            else if (is_object($return_value) && property_exists($return_value, $key))
                $return_value = $return_value->{$key};
            else
                return null;
        }

        return $return_value;
    } 

    /**
     *
     */
    final public function getPagination(int $current_offset, int $rows_per_page = 15, int $length = 10): array {
        $num_of_pages = ceil($this->file_count / $rows_per_page);
        $current_page = ceil(($current_offset + 1) / $rows_per_page);
        $middle_index = floor($length / 2);
        $paginations = [];

        if ($num_of_pages < 2)
            return $paginations;

        if ($current_page < $middle_index) {
            for ($i = 1; $i <= $num_of_pages; $i ++) {
                if ($length < 0 || $i < 1)
                    break;

                $paginations[] = [
                    'label' => $i,
                    'offset' => ($i * $rows_per_page) - $rows_per_page,
                    'is_active' => $current_page == $i,
                ];

                $length --;
            }
        } else if ($current_page > $num_of_pages - $middle_index) {
            for ($i = $num_of_pages - $length; $i <= $num_of_pages; $i ++) {
                if ($i < 1)
                    continue;

                if ($length < 0 || $i > $num_of_pages)
                    break;

                $paginations[] = [
                    'label' => $i,
                    'offset' => ($i * $rows_per_page) - $rows_per_page,
                    'is_active' => $current_page == $i,
                ];

                $length --;
            }
        } else {
            for ($i = ($current_page - $middle_index); $i <= $num_of_pages; $i ++) {
                if ($i < 1)
                    continue;

                if ($length < 0)
                    break;

                $paginations[] = [
                    'label' => $i,
                    'offset' => ($i * $rows_per_page) - $rows_per_page,
                    'is_active' => $current_page == $i,
                ];

                $length --;
            }
        }

        return $paginations;
    }
}
?>