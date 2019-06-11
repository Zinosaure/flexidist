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

abstract class DAO extends CRUD {

	/**
	*
	*
	*
	*/

	const AUTO_GENERATE_ACCESSORS = true;

	/**
	*
	*
	*
	*/
	
	public function __construct(array $data = []) {
		foreach (array_replace(static::TABLE_FIELDS, $data) as $field => $value) {
			$this->{$field} = $value;

			if (!static::AUTO_GENERATE_ACCESSORS)
				continue;

			$label = strtolower(str_replace('_', null, $field));

			if (!property_exists($this, $method = 'get' . $label) && !method_exists($this, $method))
				$this->{$method} = function() use ($field) { return $this->{$field}; };

			if (!property_exists($this, $method = 'set' . $label) && !method_exists($this, $method))
				$this->{$method} = function($value) use ($field) { $this->{$field} = $value; };
		}
	}

	/**
	*
	*
	*
	*/

	final public function __call(string $method, array $arguments = []) {
		array_unshift($arguments, $this);

		if (isset($this->{($lcmethod = strtolower($method))}) && ($function = $this->{$lcmethod}) instanceOf \Closure)
			return $function->call(... $arguments);

		throw new \Error(sprintf('Call to undefined method %s::%s()', get_called_class(), $method));
	}

	/**
	*
	*
	*
	*/

	final public function ID() {
		return $this->{static::TABLE_PRIMARY_KEY};
	}

	/**
	*
	*
	*
	*/
	
	final public function import(array $data): self {
		foreach ($data as $field => $value)
			if (array_key_exists($field, static::TABLE_FIELDS))
				$this->{$field} = $value;

		return $this;
	}

	/**
	*
	*
	*
	*/
	
	final public function export(): array {
		$data = [];

		foreach (static::TABLE_FIELDS as $field => $value)
			$data[$field] = $this->{$field};

		return $data;
	}

	/**
	*
	*
	*
	*/
	
	final public function save(): bool {
		$params = $this->export();

		if (!$this->{static::TABLE_PRIMARY_KEY}) {
			$queryString = sprintf('INSERT INTO %s (%s) VALUES (%s);', static::TABLE_NAME, implode(' , ', array_keys($params)), implode(' , ', array_fill(0, count($params), '?')));

			if ($sth = static::PDO()->execute($queryString, $params) && $this->{static::TABLE_PRIMARY_KEY} = static::PDO()->lastInsertId())
				return true;

			return false;
		}
		
		$queryString = sprintf('UPDATE %s SET %s WHERE %s = ?;', static::TABLE_NAME, implode(' = ? , ', array_keys($params)) . ' = ?', static::TABLE_PRIMARY_KEY);
		$params[] = $this->{static::TABLE_PRIMARY_KEY};

		if (static::PDO()->execute($queryString, $params))
			return true;

		return false;
	}

	/**
	*
	*
	*
	*/
	
	final public function discard(): bool {
		$queryString = sprintf('DELETE FROM %s WHERE %s = ?;', static::TABLE_NAME, static::TABLE_PRIMARY_KEY);

		if (!$this->{static::TABLE_PRIMARY_KEY} || !static::PDO()->execute($queryString, [$this->{static::TABLE_PRIMARY_KEY}])) 
			return false;

		foreach (static::TABLE_FIELDS as $field => $value)
			$this->{$field} = $value;

		return true;
	}

	/**
	*
	*
	*
	*/
	
	final public static function load($params) {
		if (!is_array($params))
			$params = [static::TABLE_PRIMARY_KEY => $params];

		$queryString = sprintf('SELECT * FROM %s WHERE %s;', static::TABLE_NAME, implode(' = ? AND ', array_keys($params)) . ' = ?');

		if (($sth = static::PDO()->execute($queryString, $params)) && $data = $sth->fetch(\PDO::FETCH_ASSOC)) 
			return new static($data);

		return false;
	}
}
?>