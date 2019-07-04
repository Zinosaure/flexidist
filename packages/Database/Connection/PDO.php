<?php

/**
*
*/
namespace Database\Connection;

/**
*
*/
final class PDO extends \PDO {

	/**
	*
	*/
	private static $PDOs = [];

	/**
	*
	*/
	final protected function __construct(string $dsn, string $username = 'root', string $password = null, array $options = []) {
		$options = array_replace_recursive([
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES => true,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_PERSISTENT => false,
			\PDO::ATTR_STATEMENT_CLASS => [__NAMESPACE__ . '\PDOStatement', [&$this]],
		], $options);

		try {
			parent::__construct($dsn, $username, $password, $options);
		} catch (\PDOException $PDOException) {
			throw $PDOException;
		}
	}

	/**
	*
	*/
	final public static function MySQL(string $database, string $username = 'root', string $password = null, string $hostname = 'localhost', int $port = 3306, array $options = []): self {
		if (isset(self::$PDOs[$dsn = 'mysql:host=' . $hostname . ';dbname=' . $database . ';port=' . $port . ';charset=utf8']) && self::$PDOs[$dsn] instanceOf self)
			return self::$PDOs[$dsn];

		return self::$PDOs[$dsn] = new self($dsn, $username, $password, array_replace([
				\PDO::ATTR_AUTOCOMMIT => true,
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
				\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
				\PDO::MYSQL_ATTR_FOUND_ROWS => true,
			], $options)
		);
	}

	/**
	*
	*/
	final public static function SQLite(string $database, array $options = []): self {
		if (isset(self::$PDOs[$dsn = 'sqlite:' . $database]) && self::$PDOs[$dsn] instanceOf self)
			return self::$PDOs[$dsn];

		if (!file_exists($database))
			@mkdir(dirname($database), 0777, true);

		if (self::$PDOs[$dsn] = new self($dsn, '', '', $options))
			@chmod($database, 0777);

		return self::$PDOs[$dsn];
	}

	/**
	*
	*/
	final public function execute(string $query_string, array $params = [], array $options = []) {
		if ($sth = $this->prepare($query_string, $options)) {
			$sth->inputParams = $params;

			if (($sth->bindValues($params)) && $sth->execute())
				return $sth;
		}
			
		return false;
	}
}
?>