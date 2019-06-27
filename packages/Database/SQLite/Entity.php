<?php

/**
*
*/
namespace Database\SQLite;

/**
*
*/
abstract class Entity extends \Database\SQLite {

    /**
    *
    */
    const ENTITY_PRIMARY_KEY = 'id';

    /**
    *
    */
    final public function ID() {
        if (static::ENTITY_PRIMARY_KEY)
            return $this->{static::ENTITY_PRIMARY_KEY};
        
        return null;
    }

    /**
    *
    */
    final public function save(): bool {
        if (!static::PDO() || !static::SQLITE_TABLE_NAME)
            return false;

        $params = [];

        foreach ($this->__exportValues() as $field => $value) {
            if (is_array($value))
                $value = json_encode($value, JSON_NUMERIC_CHECK);

            $params[$field] = $value;
        }
            
        if ($ID = $this->ID()) {
            $query_string = sprintf('UPDATE %s SET %s = ? WHERE %s = ?;', static::SQLITE_TABLE_NAME, implode(' = ?, ', array_keys($params)), static::ENTITY_PRIMARY_KEY);
            $params[] = $ID;

            if ($sth = static::PDO()->execute($query_string, $params))
                return $sth->rowCount() > 0;

            return false;
        }

        $query_string = sprintf('INSERT INTO %s(%s) VALUES(%s);', static::SQLITE_TABLE_NAME, implode(', ', array_keys($params)), implode(', ', array_fill(0, count($params), '?')));
    
        if (($sth = static::PDO()->execute($query_string, $params)) && ($id = static::PDO()->lastInsertId()) > 0)
            return static::ENTITY_PRIMARY_KEY ? ($this->{static::ENTITY_PRIMARY_KEY} = $id) > 0 : true;
        
        return false;
    }

    /**
    *
    */
    final public function discard(): bool {
        if (!static::SQLITE_TABLE_NAME || !($ID = $this->ID()))
            return false;

        if ($sth = static::PDO()->execute(sprintf('DELETE FROM %s WHERE %s = ?;', static::SQLITE_TABLE_NAME, static::ENTITY_PRIMARY_KEY), [$ID]))
            return $sth->rowCount() > 0;

        return false;
    }

    /**
    *
    */
    final public static function load($params) {
        if (!is_array($params))
            $params = [static::ENTITY_PRIMARY_KEY ?? '__no_PK' => $params];

        $query_string = sprintf('SELECT * FROM %s WHERE %s;', static::SQLITE_TABLE_NAME, implode(' = ? AND ', array_keys($params)) . ' = ?');

        if (($sth = static::PDO()->execute($query_string, $params)) && $data = $sth->fetch(\PDO::FETCH_ASSOC)) 
            return new static($data);

        return false;
    }
}
?>