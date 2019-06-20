<?php

/**
*
*/
abstract class RuntimeContent extends \Schema {

    /**
    *
    */
    private $__content = null;

    /**
    *
    */
    final public function __construct(array $data = []) {
        parent::__construct($data);
        
        $filename = sprintf('%s%s.phtml', APPLICATION_PATH, strtolower(str_replace(['\\', 'views'], ['/', 'views/templates'], get_called_class())));
        
        $this->__content = (preg_match('/\.phtml$/is', $filename) && is_file($filename)) ? file_get_contents($filename) : null;
    }

    /**
    *
    */
    final public function __toString(): string {
        return $this->compile();
    }
    
    /**
    *
    */
    final public function compile(array $data = []): string {
        ob_start();
            extract($data);
            echo eval('?>' . $this->__content);
        
        return ob_get_clean();
    }
}
?>