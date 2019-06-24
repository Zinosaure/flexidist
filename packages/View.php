<?php

/**
*
*/
class View {

    /**
    *
    */
    private $__template = null;

    /**
    *
    */
    public function __construct(string $template, array $data = []) {
        $this->__template = $template;

        foreach ($data as $field => $value)
            $this->{$field} = $value;
    }

    /**
    *
    */
    public function &__get(string $name) {
        return $this->{$name};
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