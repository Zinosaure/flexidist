<?php

/**
*
*/
class View {

    /**
    *
    */
    private $__template = null;
    private $__data = [];

    /**
    *
    */
    public function __construct(string $template, $data = []) {
        $this->__template = $template;
        $this->__data = (is_array($data) || is_object($data)) ? $data : [];
    }

    /**
    *
    */
    public function __get(string $field) {
        if (is_array($this->__data) && array_key_exists($field, $this->__data))
            return $this->__data[$field];
        else if (is_object($this->__data) && (property_exists($this->__data, $field) || method_exists($this->__data, '__get')))
            return $this->__data->{$field};

        $trace = debug_backtrace();
        trigger_error(sprintf('Undefined property via __get(): %s in %s on line %s', $field, $trace[0]['file'], $trace[0]['line']), E_USER_NOTICE);

        return null;
    }

    /**
    *
    */
    public function __set(string $field, $mixed_value) {
        if (is_array($this->__data))
            return $this->__data[$field] = $mixed_value;
       
        return $this->__data->{$field} = $mixed_value;
    }

    /**
    *
    */
    public function __call(string $method, array $args = []) {
        if (is_object($this->__data) && (method_exists($this->__data, $method) || method_exists($this->__data, '__call')))
            return $this->__data->{$method}(... $args);
 
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