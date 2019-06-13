<?php

/**
*
*/
namespace Database;

/**
*
*/
abstract class SQLite extends \Schema {

    /**
    *
    */
    const SQLITE_DATABASE_NAME = APPLICATION_PATH . 'database.db';
    const SQLITE_DATABASE_OPTIONS = [];
    const SQLITE_TABLE_NAME = null;
    const SQLITE_PRIMARY_KEY = null;

    protected $PDO = null;

    /**
    *
    */
    final public function __construct($data = [], array $schema_fields = []) {
        parent::__construct($data, $schema_fields);

        $this->PDO = \Database\PDO\PDO::SQLite(static::SQLITE_DATABASE_NAME, static::SQLITE_DATABASE_OPTIONS);
        $this->init();
    }

    /**
    *
    */
    abstract public function init();

    /**
    *
    */
    final public function getException(): array {
		return $this->PDO->getException();
    }

    /**
    *
    */
    final public function ID() {
        if (static::SQLITE_PRIMARY_KEY)
            return $this->{static::SQLITE_PRIMARY_KEY};
        
        return null;
    }
    
    /**
    *
    */
    final public function count(): int {
		$query_string = sprintf('SELECT COUNT(*) AS T FROM %s;', static::SQLITE_TABLE_NAME);

		if (($sth = $this->PDO->execute($query_string)) && $data = $sth->fetch(\PDO::FETCH_ASSOC))
			return (int) $data['T'];

		return -1;
	}

    /**
    *
    */
    final public function save(): bool {
        if (!$this->PDO || !static::SQLITE_TABLE_NAME)
            return false;

        $params = [];

        foreach ($this->serialize() as $field => $value) {
            if (is_array($value))
                $value = json_encode($value, JSON_NUMERIC_CHECK);

            $params[$field] = $value;
        }
            

        if ($ID = $this->ID()) {
            $query_string = sprintf('UPDATE %s SET %s = ? WHERE %s = ?;', static::SQLITE_TABLE_NAME, implode(' = ?, ', array_keys($params)), static::SQLITE_PRIMARY_KEY);
            $params[] = $ID;

            if ($sth = $this->PDO->execute($query_string, $params))
                return $sth->rowCount() > 0;

            return false;
        }
 
        $query_string = sprintf('INSERT INTO %s(%s) VALUES(%s);', static::SQLITE_TABLE_NAME, implode(', ', array_keys($params)), implode(', ', array_fill(0, count($params), '?')));
     
        if (($sth = $this->PDO->execute($query_string, $params)) && ($id = $this->PDO->lastInsertId()) > 0)
            return static::SQLITE_PRIMARY_KEY ? ($this->{static::SQLITE_PRIMARY_KEY} = $id) > 0 : true;
        
        return false;
    }

    /**
    *
    */
    final public function discard(): bool {
        if (!$this->PDO || !static::SQLITE_TABLE_NAME || !($ID = $this->ID()))
            return false;

        $query_tring = sprintf('DELETE FROM %s WHERE %s = ?;', static::SQLITE_TABLE_NAME, static::SQLITE_PRIMARY_KEY);

		if ($sth = $this->PDO->execute($query_string, [$ID]))
            return $sth->rowCount() > 0;

        return false;
    }

    /**
    *
    */
    final public function match(array $params = [], int $offset = -1, int $length = 20, bool $asc_order = true): array {
        $query_string = sprintf(
			'SELECT * FROM %s %s %s %s;',
				static::SQLITE_TABLE_NAME,
				!empty($params) ? 'WHERE ' . implode(' = ? AND ', array_keys($params)) . ' = ?' : null,
                static::SQLITE_PRIMARY_KEY ? sprintf('ORDER BY %s %s', static::SQLITE_PRIMARY_KEY, $asc_order ? 'ASC' : 'DESC') : null,
				$offset > -1 ? 'LIMIT ?, ?' : null
        );

		if ($offset > -1)
            $params += [$offset, $length];

		if (($sth = $this->PDO->execute($query_string, $params)) && $data = $sth->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, get_called_class()))
            return $data;
        
        return [];
    }
}
?>