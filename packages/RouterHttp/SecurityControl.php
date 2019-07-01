<?php

/**
*
*/
namespace RouterHttp;

/**
*
*/
final class SecurityControl extends \Schema {

    /**
    *
    */
    const SCHEMA_FIELD_SET_READONLY = true;
    const SCHEMA_DEFINITIONS = [
        'is_connected' => self::SCHEMA_FIELD_IS_BOOL,
        'user_permission' => self::SCHEMA_FIELD_IS_INT,
        'user_object'=> self::SCHEMA_FIELD_IS_OBJECT,
        'on_unauthorized' => self::SCHEMA_FIELD_IS_INSTANCE_OF . '\Closure',
        'on_forbidden' => self::SCHEMA_FIELD_IS_INSTANCE_OF . '\Closure',
    ];

    /**
    *
    */
    public function __construct(?int $user_permission, $user_object = null, \Closure $on_unauthorized = null, \Closure $on_forbidden = null) {
        parent::__construct([
            'is_connected' => $user_permission && $user_permission > -1,
            'user_permission' => $user_permission,
            'user_object' => (object) $user_object,
            'on_unauthorized' => $on_unauthorized ?: function() { http_response_code(401); },
            'on_forbidden' => $on_forbidden ?: function() { http_response_code(403); },
        ]);
    }

    /**
    *
    */
    public function checkPermission(int $permission_id, \RouterHttp &$RouterHttp) {
        if (!$this->is_connected)
            return $this->isUnauthorized($RouterHttp);

        if (!$this->user_permission || $this->user_permission > $permission_id)
            return $this->isForbidden($RouterHttp);
    }

    /**
    *
    */
    public function isUnauthorized(\RouterHttp &$RouterHttp) {
        exit($this->on_unauthorized->call($RouterHttp));
    }

    /**
    *
    */
    public function isForbidden(\RouterHttp &$RouterHttp) {
        exit($this->on_forbidden->call($RouterHttp));
    }
}
?>