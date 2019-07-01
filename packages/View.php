<?php

/**
*
*/
class View {

    /**
    *
    */
    private $__template = null;
    private $__inherited_object = [];

    /**
    *
    */
    public function __construct(string $template, $inherited_object = []) {
        $this->__template = $template;
        $this->__inherited_object = (is_array($inherited_object) || is_object($inherited_object)) ? $inherited_object : [];
    }

    /**
    *
    */
    public function __get(string $field) {
        if (is_array($this->__inherited_object) && array_key_exists($field, $this->__inherited_object))
            return $this->__inherited_object[$field];
        else if (is_object($this->__inherited_object) && (property_exists($this->__inherited_object, $field) || method_exists($this->__inherited_object, '__get')))
            return $this->__inherited_object->{$field};

        $trace = debug_backtrace();
        trigger_error(sprintf('Undefined property via __get(): %s in %s on line %s', $field, $trace[0]['file'], $trace[0]['line']), E_USER_NOTICE);

        return null;
    }

    /**
    *
    */
    public function __set(string $field, $mixed_value) {
        if (is_array($this->__inherited_object))
            return $this->__inherited_object[$field] = $mixed_value;
       
        return $this->__inherited_object->{$field} = $mixed_value;
    }

    /**
    *
    */
    public function __call(string $method, array $args = []) {
        if (is_object($this->__inherited_object) && (method_exists($this->__inherited_object, $method) || method_exists($this->__inherited_object, '__call')))
            return $this->__inherited_object->{$method}(... $args);
 
        throw new \Error(sprintf('Call to undefined method %s::%s()', get_called_class(), $method));
    }

    /**
    *
    */
    public function __toString(): string {
        ob_start();  
            if (preg_match('/\.phtml$/is', $filename = sprintf('%sviews/%s', APPLICATION_PATH, $this->__template)) && is_file($filename))
                include $filename;
            else
                echo eval('?>' . $this->__template);
        
        return ob_get_clean();
    }
}
?>