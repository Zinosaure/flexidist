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
    final public function dn_set(string $name, $mixed_value, bool $trans_type = false) {
        eval('
            $attribute = &$this->__dotnotations["' . implode('"]["', explode('.', $name)) . '"];
            $attribute = !is_null($attribute) ? $attribute : $mixed_value;
            
            if ($trans_type || is_null($attribute) || gettype($attribute) == gettype($mixed_value))
                $attribute = $mixed_value;
        ');

        return $this;
    }

	/**
	 *
	 */
    final public function dn_unset(string $name) {
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