<?php

/**
*
*
*
*/

namespace Database;

/**
*
*
*
*/

abstract class MySQL extends Pattern\DAO {

	/**
	*
	*
	*
	*/

	const PDO_DATABASE_NAME = 'test';
	const PDO_DATABASE_USERANME = 'root';
	const PDO_DATABASE_PASSWORD = '';
	const PDO_DATABASE_HOST = 'localhost';
	const PDO_DATABASE_PORT = 3306;
	const PDO_DATABASE_OPTIONS = [];

	/**
	*
	*
	*
	*/

	final public static function PDO(): PHP\PDO {
		return PDO\PDO::MySQL(static::PDO_DATABASE_NAME, static::PDO_DATABASE_USERNAME, static::PDO_DATABASE_PASSWORD, static::PDO_DATABASE_HOST, static::PDO_DATABASE_PORT, static::PDO_DATABASE_OPTIONS);
	}

	/**
	*
	*
	*
	*/

	final public static function showTables(): array {
		if (($sth = static::PDO()->execute(sprintf('SHOW TABLES FROM %s;', static::PDO_DATABASE_NAME))) && $data = $sth->fetchAll(\PDO::FETCH_ASSOC))
			return $data;

		return [];
	}
}
?>