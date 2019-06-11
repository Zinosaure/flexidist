<?php

/**
*
*/
namespace Database\Engine;

/**
*
*/
class SQLite extends \Schema\Schema {

    /**
    *
    */
    const SQLITE_DATABASE_NAME = APPLICATION_PATH . 'database.db';
    const SQLITE_DATABASE_OPTIONS = [];
    const SQLITE_TABLE_NAME = null;

    public $PDO = null;

    /**
    *
    */
    final public static function PDO(): \Database\PDO {
        return \Database\PDO::SQLite(static::SQLITE_DATABASE_NAME, static::SQLITE_DATABASE_OPTIONS);
    }

    final public function getErrors(): array {
		return $this->PDO->getErrors();
	}

    /**
    *
    */
    final public function save(): bool {
        if (!$this->PDO || !static::SQLITE_TABLE_NAME)
            return false;

        $params = [];

        foreach ($this->__schema_attributes as $name => $is) {
            if (!in_array($is, [
                self::SCHEMA_VALIDATE_IS_CONTENT, 
                self::SCHEMA_VALIDATE_IS_STRING, 
                self::SCHEMA_VALIDATE_IS_NUMERIC, 
                self::SCHEMA_VALIDATE_IS_INT, 
                self::SCHEMA_VALIDATE_IS_INTEGER, 
                self::SCHEMA_VALIDATE_IS_BOOLEAN
            ]))
                $params[$name] = (string) $this->{$name};
            else
                $params[$name] = $this->{$name};
        }

        if ($ID = $this->ID()) {
            $query_string = sprintf('UPDATE %s SET %s = ? WHERE %s = ?;', static::SQLITE_TABLE_NAME, implode(' = ?, ', array_keys($params)), static::SCHEMA_PRIMARY_KEY);
            $params[] = $ID;

            if ($sth = $this->PDO->execute($query_string, $params))
                return $sth->rowCount() > 0;

            return false;
        }
 
        $query_string = sprintf('INSERT INTO %s(%s) VALUES(%s);', static::SQLITE_TABLE_NAME, implode(', ', array_keys($params)), implode(', ', array_fill(0, count($params), '?')));
     
        if (($sth = $this->PDO->execute($query_string, $params)) && ($id = $this->PDO->lastInsertId()) > 0)
            return static::SCHEMA_PRIMARY_KEY ? ($this->{static::SCHEMA_PRIMARY_KEY} = $id) > 0 : true;
        
        return false;
    }

    /**
    *
    */
    final public function discard(): bool {
        if (!$this->PDO || !static::SQLITE_TABLE_NAME || !($ID = $this->ID()))
            return false;

        $query_tring = sprintf('DELETE FROM %s WHERE %s = ?;', static::SQLITE_TABLE_NAME, static::SCHEMA_PRIMARY_KEY);

		if ($sth = $this->PDO->execute($query_string, [$ID]))
            return $sth->rowCount() > 0;

        return false;
    }

    /**
    *
    */
    final public static function createRepository() {
        /**
        class HttpRequestRepository extends \Schema\Schema {

    const SCHEMA_VALIDATE_ATTRIBUTES = [
        'http_requests' => self::SCHEMA_VALIDATE_IS_LIST_OF . 'HttpRequest',
    ];
    public function __construct() {
        if ($sth = HttpRequest::PDO()->execute(sprintf('SELECT * FROM %s', HttpRequest::SQLITE_TABLE_NAME)))
            parent::__construct($sth->fetchAll());
    }
} */
    }
}
?>