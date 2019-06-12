<?php

/**
*
*/
class HttpRequest extends \database\SQLite {

    /**
    *
    */
    const SQLITE_DATABASE_OPTIONS = [];
    const SQLITE_TABLE_NAME = 'http_requests';
    const SQLITE_PRIMARY_KEY = 'id';

    const SCHEMA_FIELDS = [
        'id' => self::SCHEMA_FIELD_IS_NUMERIC,
        'slug' => self::SCHEMA_FIELD_IS_STRING,
        'status_code' => self::SCHEMA_FIELD_IS_INT,
        'title' => self::SCHEMA_FIELD_IS_STRING,
        'description' => self::SCHEMA_FIELD_IS_STRING,
        'content' => self::SCHEMA_FIELD_IS_CONTENT,
        'metadata' => [
            'assoc_template' => self::SCHEMA_FIELD_IS_STRING,
            'keywords' => self::SCHEMA_FIELD_IS_STRING,
            'robots' => self::SCHEMA_FIELD_IS_STRING,
            'breadcrumb' => self::SCHEMA_FIELD_IS_STRING,
            'canonical_url' => self::SCHEMA_FIELD_IS_STRING,
            'redirection_url' => self::SCHEMA_FIELD_IS_STRING,
            'created_date' => self::SCHEMA_FIELD_IS_INT,
        ],
    ];

    /**
    *
    */
    public function init() {
        $this->PDO->execute('CREATE TABLE IF NOT EXISTS http_requests (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT, 
            slug                TEXT NOT NULL UNIQUE,
            status_code         INTEGER NOT NULL, 
            title               TEXT NOT NULL, 
            description         TEXT NOT NULL, 
            content             BLOB NOT NULL,
            metadata            TEXT NULL
        );');
    }
}
?>