<?php

/**
*
*/
namespace RouterHttp;

/**
*
*/
class Request extends \Schema {

    /**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = true;
    const SCHEMA_FIELDS = [
        'servers' => self::SCHEMA_FIELD_IS_OBJECT,
        'args' => self::SCHEMA_FIELD_IS_OBJECT,
        'posts' => self::SCHEMA_FIELD_IS_OBJECT,
        'cookies' => self::SCHEMA_FIELD_IS_OBJECT,
        'sessions' => self::SCHEMA_FIELD_IS_OBJECT,
        'attributes' => [
            'REQUEST_METHOD' => self::SCHEMA_FIELD_IS_STRING,
            'DOCUMENT_ROOT' => self::SCHEMA_FIELD_IS_STRING,
            'REQUEST_URI' => self::SCHEMA_FIELD_IS_STRING,
            'REQUEST_QUERY_URI' => self::SCHEMA_FIELD_IS_STRING,
            'SERVER_DOCUMENT_ROOT' => self::SCHEMA_FIELD_IS_STRING,
            'FULL_REQUEST_URI' => self::SCHEMA_FIELD_IS_STRING,
            'FULL_REQUEST_QUERY_URI' => self::SCHEMA_FIELD_IS_STRING,
            'REQUEST_URIs' => self::SCHEMA_FIELD_IS_LIST,
        ],
    ];

    /**
    *
    */
    public function __construct() {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        parent::__construct([
            'servers' => (object) $_SERVER,
            'args' => (object) $_GET,
            'posts' => (object) $_POST,
            'cookies' => (object) $_COOKIE,
            'sessions' => (object) ($session_started ? $_SESSION : []),
            'attributes' => [
                'REQUEST_METHOD' => strtoupper($_SERVER['REQUEST_METHOD']),
                'DOCUMENT_ROOT' => DOCUMENT_ROOT,
                'REQUEST_URI' => REQUEST_URI,
                'REQUEST_QUERY_URI' => REQUEST_QUERY_URI,
                'SERVER_DOCUMENT_ROOT' => SERVER_NAME . DOCUMENT_ROOT,
                'FULL_REQUEST_URI' => SERVER_NAME . DOCUMENT_ROOT . REQUEST_URI,
                'FULL_REQUEST_QUERY_URI' => SERVER_NAME . DOCUMENT_ROOT . REQUEST_QUERY_URI,
                'REQUEST_URIs' => explode('/', trim(REQUEST_URI, '/')),
            ],
        ]);
    }

    /**
    *
    */
    public static function send(string $request_uri, array $options = []) {
        return @file_get_contents($request_uri, false, stream_context_create(array_replace_recursive([
            'http' => [
                'method' => 'GET',
            ]
        ], $options)));
    }

    /**
    *
    */
    public static function redirectTo(string $url, int $status_code = 301) {
        exit(header('Location: ' . $url, false, $status_code));
    }
}
?>