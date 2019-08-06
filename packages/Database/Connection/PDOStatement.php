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
	public $inputParams = [];

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

	/**
	*
	*/
	final public function getLastInsertId() {
        return $this->PDO->lastInsertId();
    }

	/**
    *
    */
    final public function getResultCount(): int {
		$query_string = str_replace(';', null, trim($this->queryString));
		$query_string = sprintf('SELECT COUNT(*) AS T FROM (%s)', preg_replace('/LIMIT(.+)(;|\)|$)/isU', null, $query_string));
		
		if (($sth = $this->PDO->execute($query_string, $this->inputParams)) && $data = $sth->fetch(\PDO::FETCH_ASSOC)) 
            return (int) $data['T'];

        return -1;
    }
}
?>