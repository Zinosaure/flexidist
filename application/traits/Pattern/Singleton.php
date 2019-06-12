<?php

/**
*
*/
namespace Pattern;

/**
*
*/
trait Singleton {

	/**
	*
	*/
	protected static $__SingletonInstance = null;

	/**
	*
	*/
	final public static function getInstance(): self {
		if (!static::$__SingletonInstance)
			static::$__SingletonInstance = new static(... func_get_args());

		return static::$__SingletonInstance;
	}

	/**
	*
	*/
	final protected function __clone() {}
}
?>