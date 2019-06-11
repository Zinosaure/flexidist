<?php

/**
*
*/

namespace Dashboard;

/**
*
*/
class HttpRequest extends \Database\Engine\SQLite {

    /**
    *
    */
    
    const SQLITE_DATABASE_NAME = './database.db';
    const SQLITE_DATABASE_OPTIONS = [];
    const SQLITE_TABLE_NAME = 'http_requests';

    const SCHEMA_VALIDATE_ATTRIBUTES = [
        'id' => self::SCHEMA_VALIDATE_IS_NUMERIC,
        'slug' => self::SCHEMA_VALIDATE_IS_STRING,
        'display_type' => self::SCHEMA_VALIDATE_IS_INT,
        'title' => self::SCHEMA_VALIDATE_IS_STRING,
        'metadata' => [
            'description' => self::SCHEMA_VALIDATE_IS_STRING,
            'keywords' => self::SCHEMA_VALIDATE_IS_STRING,
            'robots' => self::SCHEMA_VALIDATE_IS_STRING,
            'canonical_url' => self::SCHEMA_VALIDATE_IS_STRING,
            'breadcrumb' => self::SCHEMA_VALIDATE_IS_STRING,
        ],
        'content' => self::SCHEMA_VALIDATE_IS_CONTENT,
        'created_date' => self::SCHEMA_VALIDATE_IS_INT,
    ];
    const SCHEMA_PRIMARY_KEY = 'id';

    /**
    *
    */
    public function __construct(array $data = []) {
        $this->PDO = self::PDO();
        $this->PDO->execute('CREATE TABLE IF NOT EXISTS http_requests (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT, 
            slug                TEXT NOT NULL,
            display_type        INTEGER NOT NULL, 
            title               TEXT NOT NULL, 
            metadata            TEXT NULL,
            content             BLOB NOT NULL, 
            created_date        INTEGER NOT NULL, 
 
            UNIQUE(slug) ON CONFLICT IGNORE
        );');

        parent::__construct(array_replace_recursive([
            'id' => null,
            'slug' => null,
            'display_type' => null,
            'title' => null,
            'metadata' => $data['metadata'] ?? [],
            'content' => null,
            'created_date' => time(),
        ], $data));
    }
}
?>