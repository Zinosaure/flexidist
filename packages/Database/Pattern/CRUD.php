<?php

/**
*
*
*
*/

namespace Database\Pattern;

/**
*
*
*
*/

abstract class CRUD {

	/**
	*
	*
	*
	*/

	const TABLE_NAME = null;
	const TABLE_PRIMARY_KEY = 'ID';
	const TABLE_FIELDS = [];

	/**
	*
	*
	*
	*/

	abstract static function PDO(): \database\PHP\PDO;

	/**
	*
	*
	*
	*/

	final public static function count(): int {
		$queryString = sprintf('SELECT COUNT(*) AS T FROM %s;', static::TABLE_NAME);

		if (($sth = static::PDO()->execute($queryString)) && $data = $sth->fetch(\PDO::FETCH_ASSOC))
			return (int) $data['T'];

		return -1;
	}

	/**
	*
	*
	*
	*/

	final public static function getFirstOne() {
		$queryString = sprintf('SELECT * FROM %s ORDER BY %s ASC LIMIT 1;', static::TABLE_NAME, static::TABLE_PRIMARY_KEY);

		if (($sth = static::PDO()->execute($queryString)) && $data = $sth->fetch(\PDO::FETCH_ASSOC))
			return new static($data);

		return false;
	}

	/**
	*
	*
	*
	*/

	final public static function getLastOne() {
		$queryString = sprintf('SELECT * FROM %s ORDER BY %s DESC LIMIT 1;', static::TABLE_NAME, static::TABLE_PRIMARY_KEY);

		if (($sth = static::PDO()->execute($queryString)) && $data = $sth->fetch(\PDO::FETCH_ASSOC))
			return new static($data);

		return false;
	}

	/**
	*
	*
	*
	*/

	final public static function select(array $params = [], int $start = -1, int $offset = 20, bool $ascending = true): array {
		$queryString = sprintf(
			'SELECT * FROM %s %s ORDER BY %s %s %s;', 
				static::TABLE_NAME,
				!empty($params) ? 'WHERE ' . implode(' = ? , ', array_keys($params)) . ' = ?' : null,
				static::TABLE_PRIMARY_KEY, 
				$ascending ? 'ASC' : 'DESC', 
				$start > -1 ? 'LIMIT ?, ?' : null
		);

		if ($start > -1)
			$params += [$start, $offset];

		if (($sth = static::PDO()->execute($queryString, $params)) && $data = $sth->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, get_called_class()))
			return $data;

		return [];
	}

	/**
	*
	*
	*
	*/

	final public static function insert(array $data, array $duplicate = []) {
		if (empty($duplicate))
			$duplicate = null;
		else {
			foreach ($duplicate as $i => $field)
				$duplicate[$i] = sprintf('%s = VALUES(%s)', $field, $field); 

			$duplicate = sprintf('ON DUPLICATE KEY UPDATE %s', implode(' , ', $duplicate));
		}

		$params = [];

		foreach ($data as $name => $value)
			if (array_key_exists($name, static::TABLE_FIELDS))
				$params[$name] = $value;

		$queryString = sprintf('INSERT INTO %s (%s) VALUES (%s) %s;', static::TABLE_NAME, implode(' , ', array_keys($params)), implode(' , ', array_fill(0, count($params), '?')), $duplicate);
	
		if ($sth = static::PDO()->execute($queryString, $params))
			return static::PDO()->lastInsertId();

		return false;
	}

	/**
	*
	*
	*
	*/

	final public static function bulkinsert(array $inputs, int $chunkSize = 100): int {
		$rowCount = 0;

		foreach (array_chunk($inputs, $chunkSize) as $chunkId => $data) {
			$placeholders = [];

			foreach ($data as $i => $tmpData) 
				foreach (array_replace(static::TABLE_FIELDS, (array) $tmpData) as $name => $value)
					if (array_key_exists($name, static::TABLE_FIELDS))
						$placeholders[$i][] = $value;

			$params = [];

			foreach ($placeholders as $i => $tmpData) {
				foreach ($tmpData as $name => $value)
					$params[] = $value;

				$placeholders[$i] = '(' . implode(' , ', array_fill(0, count($tmpData), '?')) . ')';
			}

			$queryString = sprintf('INSERT INTO %s (%s) VALUES %s;', static::TABLE_NAME, implode(' , ', array_keys(static::TABLE_FIELDS)), implode(' , ', $placeholders));
			
			if ($sth = static::PDO()->execute($queryString, $params))
				$rowCount += $sth->rowCount();
		}

		return (int) $rowCount;
	}

	/**
	*
	*
	*
	*/

	final public static function update(array $data, array $PKs): int {
		$params = [];

		foreach ($data as $name => $value)
			if (array_key_exists($name, static::TABLE_FIELDS))
				$params[$name] = $value;

		$queryString = sprintf('UPDATE %s SET %s WHERE %s IN(%s);', static::TABLE_NAME, implode(' = ? , ', array_keys($params)) . ' = ?', static::TABLE_PRIMARY_KEY, implode(' , ', array_fill(0, count($PKs), '?')));

		foreach ($PKs as $ID)
			$params[] = $ID;
		
		if ($sth = static::PDO()->execute($queryString, $params))
			return (int) $sth->rowCount();

		return -1;
	}

	/**
	*
	*
	*
	*/

	final public static function delete(array $PKs): int {
		$queryString = sprintf('DELETE FROM %s WHERE %s IN(%s);', static::TABLE_NAME, static::TABLE_PRIMARY_KEY, implode(' , ', array_fill(0, count($PKs), '?')));

		if ($sth = static::PDO()->execute($queryString, $PKs))
			return (int) $sth->rowCount();

		return -1;
	}

	/**
	*
	*
	*
	*/

	final public function getErrors() {
		return static::PDO()->getErrors();
	}
}
?>