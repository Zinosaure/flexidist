<?php

/**
*
*/
namespace Database\Connection;

/**
*
*/
final class PDOStatement extends \PDOStatement {

	/**
	*
	*/
	public $PDO = null;
	public $queryString = null;

	/**
	*
	*/
	protected function __construct(PDO &$PDO) {
		$this->PDO = $PDO;
	}

	/**
	*
	*/
	final public function __set($name, $value) {
		return;
	}

	/**
	*
	*/
	final public function bindParams(array $params) {
		foreach (array_values($params) as $i => $value) {
			$var_type = \PDO::PARAM_STR;

			if (is_null($value))
				$var_type = \PDO::PARAM_NULL;
			else if (is_bool($value))
				$var_type = \PDO::PARAM_BOOL;
			else if (is_int($value))
				$var_type = \PDO::PARAM_INT;

			if (!$this->bindParam(++ $i, $value, $var_type))
				return false;
		}

		return true;
	}

	/**
	*
	*/
	final public function bindValues(array $params) {
		foreach (array_values($params) as $i => $value) {
			$var_type = \PDO::PARAM_STR;

			if (is_null($value))
				$var_type = \PDO::PARAM_NULL;
			else if (is_bool($value))
				$var_type = \PDO::PARAM_BOOL;
			else if (is_int($value))
				$var_type = \PDO::PARAM_INT;

			if (!$this->bindValue(++ $i, $value, $var_type))
				return false;
		}

		return true;
	}
}
?>