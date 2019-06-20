<?php

/**
*
*/
abstract class ResponseViewContent extends \Schema {

    /**
    *
    */
    const SCHEMA_FIELD_MISMATCH_SET_NULL = false;
    const SCHEMA_FIELDS = [
        'template' => self::SCHEMA_FIELD_IS_CONTENT,
        'template_vars' => self::SCHEMA_FIELD_IS_OBJECT,
    ];

    const TEMPLATE_VARIABLES_SCHEMA = [];

    /**
    *
    */
    final public function __construct(array $template_vars = []) {
        $filename = sprintf('%s%s.phtml', APPLICATION_PATH, strtolower(str_replace(['\\', 'views'], ['/', 'views/templates'], get_called_class())));

        if (!file_exists($filename))
            file_put_contents($filename, var_export(static::TEMPLATE_VARIABLES_SCHEMA, true));

        parent::__construct([
            'template' => @file_get_contents($filename),
            'template_vars' => new \Schema($template_vars, static::TEMPLATE_VARIABLES_SCHEMA),
        ]);
    }

    /**
    *
    */
    final public function __toString(): string {
        ob_start();
            echo eval('?>' . $this->template);
        
        return ob_get_clean();
    }
}
?>