<?php

/**
*
*/
namespace Flexidist\Server;

/**
*
*/
abstract class FileExplorer {

    /**
    *
    */
    private $Schema = null;
    private $Schema_name = null;
    private $data_path = null;
    private $glob_pattern = '*.json';
    private $cache_data = [];

    private $metadata_filename = null;
    private static $metadata = [];

    /**
    *
    */
    final public function __construct(\Flexidist\Server\Schema\JSON $Schema, string $data_path = '.', string $glob_pattern = '*.json') {
        $this->Schema = $Schema;
        $this->Schema_name = get_called_class();
        $this->data_path = $data_path;
        $this->glob_pattern = $glob_pattern;
        self::$metadata[$this->Schema_name] = ['IDs' => [], 'indexation' => [], 'count_file' => 0];

        if ($is_indexed = file_exists($this->metadata_filename = sprintf('%s/_%s', $data_path, md5($this->Schema_name))))
            self::$metadata[$this->Schema_name] = json_decode(file_get_contents($this->metadata_filename), JSON_OBJECT_AS_ARRAY);
        
        $count_file = count($files = glob(sprintf('%s/%s', $data_path, $glob_pattern)));

        if (!$is_indexed || $count_file != self::$metadata[$this->Schema_name]['count_file']) {
            foreach ($files as $filename)
                $this->append($filename);

            $this->commit();
        }
    }

    /**
    *
    */

    final public function commit(): bool {
        return (bool) file_put_contents($this->metadata_filename, json_encode(self::$metadata[$this->Schema_name], JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK));
    }

    /**
    *
    */
    final public function open(string $filename): ?array {
        if (!file_exists($filename))
            return null;

        $basename = basename($filename);
        $metadata = [];
        
        if ($this->Schema::SCHEMA_PRIMARY_KEY) {
            $Schema = $this->Schema::create((array) @json_decode(file_get_contents($filename), JSON_OBJECT_AS_ARRAY));
            $metadata['IDs'][$ID = $Schema->ID()] = $basename;
            
            if ($this->Schema::SCHEMA_INDEX_KEYS) {
                foreach ($this->Schema::SCHEMA_INDEX_KEYS as $name) {
                    if (!isset($this->Schema::VALIDATE_SCHEMA[$name]))
                        continue;
                    
                    if ($Schema->{$name} instanceOf \Flexidist\Server\Schema\JSON)
                        $metadata['indexation'][$name] = $Schema->{$name}->exportIndexes($ID, $basename);
                    else
                        $metadata['indexation'][$name][$Schema->{$name}][$ID] = $basename;
                }
            }
        } else
            $metadata['IDs'][] = $basename;

        return $metadata;
    }

    /**
    *
    */
    final public function append(string $filename): bool {
        if (!file_exists($filename) || in_array($basename = basename($filename), self::$metadata[$this->Schema_name]['IDs']))
            return false;

        else if ($metadata = $this->open($filename)) {
            self::$metadata[$this->Schema_name] = array_replace_recursive(self::$metadata[$this->Schema_name], $metadata);
            self::$metadata[$this->Schema_name]['count_file'] ++;
        
            return true;
        }

        return false;
    }

    /**
    *
    */
    final public function update(string $filename): bool {
        if ($metadata = $this->open($filename)) {
            self::$metadata[$this->Schema_name] = array_replace_recursive(self::$metadata[$this->Schema_name], $metadata);
        
            return true;
        }
        
        return false;
    }

    /**
    *
    */
    final public function delete(string $filename): bool {
        if (!in_array($basename = basename($filename), self::$metadata[$this->Schema_name]['IDs']))
            return false;
        
        $ID = array_search($basename, self::$metadata[$this->Schema_name]['IDs'], true);

        foreach (self::$metadata[$this->Schema_name]['indexation'] as $index_name => $value) {
            if (isset(self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID]))
                unset(self::$metadata[$this->Schema_name]['indexation'][$index_name][$ID]);
        }

        unset(self::$metadata[$this->Schema_name]['IDs'][$ID]);
        self::$metadata[$this->Schema_name]['count_file'] --;

        return true;
    }

    /**
    *
    */
    final public function find(string $name, $mixed_value): array {
        return eval('return self::$metadata[$this->Schema_name]["indexation"]["' . implode('"]["', explode('.', $name)) . '"][$mixed_value] ?? [];');
    }

    /**
     * 
     */
     public function offsetLength(int $offset = 1, int $length = null): self {
        $offset = $offset < 1 ? 1 : $offset;
        
        $this->cached_data = array_slice(self::$metadata[$this->Schema_name]['IDs'], $offset - 1, $length);
        $this->cached_data_count = count($this->cached_data);

        return $this;
    }

    /**
     * 
     */
    public function sort(int $ordering = 0): self {
        if ($ordering > 0) {
            natsort(self::$metadata[$this->Schema_name]['IDs']);
            natsort($this->cached_data);
        } else if ($ordering < 0) {
            natsort(self::$metadata[$this->Schema_name]['IDs']);
            natsort($this->cached_data);

            self::$metadata[$this->Schema_name]['IDs'] = array_reverse(self::$metadata[$this->Schema_name]['IDs']);
            $this->cached_data = array_reverse($this->cached_data);
        } 

        return $this;
    }

    /**
     * 
     */
    public function onEach(\Closure $callback): self {
        $this->cached_data = array_filter(array_map($callback, $this->cached_data));
        $this->cached_data_count = count($this->cached_data);

        return $this;
    }

    /**
     *
     */
    final public function getPagination(int $current_offset, int $rows_per_page = 15, int $length = 10): array {
        $num_of_pages = ceil(self::$metadata[$this->Schema_name]['count_file'] / $rows_per_page);
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