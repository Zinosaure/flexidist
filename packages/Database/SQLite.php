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

abstract class SQLite extends Pattern\DAO {

	/**
	*
	*
	*
	*/

	const PDO_DATABASE_NAME = null;
	const PDO_DATABASE_OPTIONS = [];

	/**
	*
	*
	*
	*/

	final public static function PDO(): PHP\PDO {
		return PHP\PDO::SQLite(static::PDO_DATABASE_NAME, static::PDO_DATABASE_OPTIONS);
	}

	/**
	*
	*
	*
	*/

	final public static function showTables(): array {
		if (($sth = static::PDO()->execute('SELECT name FROM sqlite_master WHERE type = "table" AND name NOT LIKE "sqlite_%";')) && $data = $sth->fetchAll(\PDO::FETCH_ASSOC))
			return $data;

		return [];
	}
}
?>