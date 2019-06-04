<?php

/**
 *
 */
namespace traits;

/**
 *
 */
trait dotnotation {

	/**
	 *
	 */
    protected $__dotnotations = [];
	
	/**
	 *
	 */
    final public function dn_init(array $dotnotations) {
        $this->__dotnotations = $dotnotations;

        return $this;
    }
    
	/**
	 *
	 */
    final public function dn_get(string $name = null, $default = null) {
        if (is_null($name))
            return $this->__dotnotations;

        return eval('return $this->__dotnotations["' . implode('"]["', explode('.', $name)) . '"] ?? $default;');
    }
	
	/**
	 *
	 */
    final public function dn_set(string $name, $mixed_value, bool $transtype = false, bool $throw_exception = true) {
        eval('$attribute = &$this->__dotnotations["' . implode('"]["', explode('.', $name)) . '"];');
        $attribute = !is_null($attribute) ? $attribute : $mixed_value;
            
        if ($transtype || is_null($attribute) || gettype($attribute) == gettype($mixed_value))
            $attribute = $mixed_value;
        else if ($throw_exception)
            throw new \UnexpectedValueException(sprintf('The dotnotation (%s) value must be a `%s`, `%s` given.', $name, gettype($attribute), gettype($mixed_value)));

        return $this;
    }

	/**
	 *
	 */
    final public function dn_unset(string $name, string ...$names) {
    	foreach(func_get_args() as $name)
        	eval('unset($this->__dotnotations["' . implode('"]["', explode('.', $name)) . '"]);');
    }

	/**
	 *
	 */
    final public function dn_has(string $name): bool {
        return eval('return isset($this->__dotnotations["' . implode('"]["', explode('.', $name)) . '"]);');
    }
}
?>