<?php

/**
*
*/
namespace RouterHttp;

/**
*
*/
class SecurityControl extends \Schema {

    /**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = true;
    const SCHEMA_FIELDS = [
        'level' => self::SCHEMA_FIELD_IS_INT,
        'userdata'=> self::SCHEMA_FIELD_IS_OBJECT,
        'callback' => self::SCHEMA_FIELD_IS_INSTANCE_OF . '\Closure',
    ];

    protected $callback = null;

    /**
    *
    */
    public function __construct(int $level, \Schema $userdata, \Closure $callback = null) {
        parent::__construct([
            'level' => $level,
            'userdata' => (object) $userdata,
            'callback' => $callback ?: function() { http_response_code(401); }
        ]);
    }

    /**
    *
    */
    public function setCheckpoint(int $checkpoint_level, \RouterHttp $RouterHttp) {
        if ($this->level > $checkpoint_level)
            return $this->isRestricted($RouterHttp);
    }

    /**
    *
    */
    public function isRestricted(\RouterHttp $RouterHttp = null) {
        exit(call_user_func_array($this->callback->bindTo($RouterHttp ?: $this), []));
    }
}
?>