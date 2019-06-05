<?php

/**
*
*/
namespace LocalStorage;

/**
*
*/
abstract class Repository {

    /**
    *
    */
    protected $data = [];

    /**
    *
    */
    final public function __construct(array $data, Schema $Schema) {
        $this->data = array_map(function($value) use ($Schema) {
            return (clone $Schema)->bind($value);
        }, $data);
    }

    /**
    *
    */
    final public static function load(string $filename, Schema $Schema): self {
        return new static((array) json_decode(@file_get_contents($filename), JSON_OBJECT_AS_ARRAY), $Schema);
    }

    /**
    *
    */
    public function insert(array $data): bool {

    }

    /**
    *
    */
    public function update(array $data): bool {

    }

    /**
    *
    */
    public function delete(array $data): bool {

    }

    /**
    *
    */
    public function count(): int {
        return count($this->data);
    }

    /**
    *
    */
    public function save(): bool {

    }

    /**
     * 
     */
     public function getPagination(int $current_offset, int $rows_per_page = 15, int $length = 10): array {
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