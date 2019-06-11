<?php

/**
*
*/
namespace Pattern;

/**
 *
 */
trait Registry {

	/**
	 *
	 */
    protected static $__RegistryData = [];
	
	/**
	 *
	 */
    final protected static function reginit(array $registryData) {
        self::$__RegistryData = $registryData;
    }
    
	/**
	 *
	 */
    final protected static function regget(string $name = null, $default = null) {
        if (is_null($name))
            return self::$__RegistryData;

        return eval('return self::$__RegistryData["' . implode('"]["', explode('.', $name)) . '"] ?? $default;');
    }
	
	/**
	 *
	 */
    final protected static function regset(string $name, $mixed_value, bool $transtype = false, bool $throw_exception = true) {
        eval('$attribute = &self::$__RegistryData["' . implode('"]["', explode('.', $name)) . '"];');
        $attribute = !is_null($attribute) ? $attribute : $mixed_value;
            
        if ($transtype || is_null($attribute) || gettype($attribute) == gettype($mixed_value))
            $attribute = $mixed_value;
        else if ($throw_exception)
            throw new \UnexpectedValueException(sprintf('The dotnotation (%s) value must be a `%s`, `%s` given.', $name, gettype($attribute), gettype($mixed_value)));
    }

	/**
	 *
	 */
    final protected static function regunset(string $name, string ...$names) {
    	foreach(func_get_args() as $name)
        	eval('unset(self::$__RegistryData["' . implode('"]["', explode('.', $name)) . '"]);');
    }

	/**
	 *
	 */
    final protected static function reghas(string $name): bool {
        return eval('return isset(self::$__RegistryData["' . implode('"]["', explode('.', $name)) . '"]);');
    }
}
?>