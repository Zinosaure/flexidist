<?php

/**
*
*/
namespace Database\SQLite;

/**
*
*/
abstract class Repository extends Entity {

    /**
    *
    */
    final public static function count(): int {
        if (($sth = static::PDO()->execute(sprintf('SELECT COUNT(*) AS T FROM %s;', static::SQLITE_TABLE_NAME))) && $data = $sth->fetch(\PDO::FETCH_ASSOC))
            return (int) $data['T'];

        return -1;
    }

    /**
    *
    */
    final public static function find(array $params = [], int $offset = -1, int $length = 20, bool $asc_order = true): array {
        $query_string = sprintf(
            'SELECT * FROM %s %s %s %s;',
                static::SQLITE_TABLE_NAME,
                !empty($params) ? 'WHERE ' . implode(' = ? AND ', array_keys($params)) . ' = ?' : null,
                static::ENTITY_PRIMARY_KEY ? sprintf('ORDER BY %s %s', static::ENTITY_PRIMARY_KEY, $asc_order ? 'ASC' : 'DESC') : null,
                $offset > -1 ? 'LIMIT ?, ?' : null
        );

        if ($offset > -1)
            $params += [$offset, $length];

        if (($sth = static::PDO()->execute($query_string, $params)) && $data = $sth->fetchAll(\PDO::FETCH_ASSOC))
            return array_map(function($temp_data) {
                return new static($temp_data);
            }, $data);
        
        return [];
    }
}
?>