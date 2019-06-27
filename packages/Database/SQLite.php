<?php

/**
*
*/
namespace Database;

/**
*
*/
abstract class SQLite extends \Schema {

    /**
    *
    */
    const SQLITE_DATABASE_NAME = APPLICATION_PATH . 'database.db';
    const SQLITE_DATABASE_OPTIONS = [];
    
    const SQLITE_TABLE_NAME = null;
    const SQLITE_TABLE_CONSTRAINTS = null;

    const SQLITE_COLUMN_IS_AUTOINCREMENT = '|AUTOINCREMENT';
    const SQLITE_COLUMN_IS_PRIMARY_KEY = '|PRIMARY KEY';
    const SQLITE_COLUMN_IS_NOT_NULL = '|NOT NULL';
    const SQLITE_COLUMN_IS_UNIQUE = '|UNIQUE';
    const SQLITE_COLUMN_REFERENCES_IS = '|REFERENCES ';
    const SQLITE_COLUMN_DEFAULT_IS = '|DEFAULT ';
    const SQLITE_COLUMN_CHECK_IS = '|CHECK ';

    /**
    *
    */
    final public static function PDO(): \Database\Connection\PDO {
        return \Database\Connection\PDO::SQLite(static::SQLITE_DATABASE_NAME, static::SQLITE_DATABASE_OPTIONS);
    }

    /**
    *
    */
    final public static function _createTable(?bool &$executed = false, ?string &$query_string = null) {
        $columns = [];
        
        foreach (static::SCHEMA_DEFINITIONS as $field => $field_type) {
            if (is_array($field_type))
                $columns[] = sprintf('`%s` BLOB NULL', $field);
            else {
                $constraints = explode('|', $field_type);
                $data_type = 'BLOB';
    
                if (in_array($field_type = array_shift($constraints), [self::SCHEMA_FIELD_IS_INT, self::SCHEMA_FIELD_IS_INTEGER]) || in_array('AUTOINCREMENT', $constraints))
                    $data_type = 'INTEGER';
                else if (in_array($field_type, [self::SCHEMA_FIELD_IS_FLOAT, self::SCHEMA_FIELD_IS_DOUBLE]))
                    $data_type = 'REAL';
                else if (in_array($field_type, [self::SCHEMA_FIELD_IS_NUMERIC, self::SCHEMA_FIELD_IS_BOOL, self::SCHEMA_FIELD_IS_BOOLEAN]))
                    $data_type = 'NUMERIC';
                else if (in_array($field_type, [self::SCHEMA_FIELD_IS_STRING, self::SCHEMA_FIELD_IS_CONTENT]))
                    $data_type = 'TEXT';
                    
                $columns[] = sprintf('`%s` %s %s', $field, $data_type, implode(' ', $constraints) ?: 'NULL');
            }
        }

        if (static::SQLITE_TABLE_CONSTRAINTS)
            $columns[] = (string) static::SQLITE_TABLE_CONSTRAINTS;

        return $executed = (bool) self::PDO()->execute($query_string = sprintf('CREATE TABLE IF NOT EXISTS `%s` (%s);', static::SQLITE_TABLE_NAME, implode(', ', $columns)));
    }

    /**
    *
    */
    final public static function _dropTable(?bool &$executed = false, ?string &$query_string = null) {
        return $executed = (bool) self::PDO()->execute($query_string = sprintf('DROP TABLE IF EXISTS %s;', static::SQLITE_TABLE_NAME));
    }

    /**
    *
    */
    final public static function _truncateTable(?bool &$executed = false, ?string &$query_string = null) {
        return $executed = (bool) self::PDO()->execute($query_string = sprintf('DELETE FROM %s;', static::SQLITE_TABLE_NAME));
    }

    /**
    *
    */
    final public static function _pragmaTable(?string &$query_string = null) {
        if (($sth = static::PDO()->execute($queryString = sprintf('PRAGMA table_info(%s);', static::SQLITE_TABLE_NAME))) && $data = $sth->fetch(\PDO::FETCH_ASSOC)) 
            return $data;

        return null;
    }

    /**
    *
    */
    final public static function _describeTable(?string &$query_string = null) {
        if (($sth = static::PDO()->execute($queryString = sprintf('SELECT sql AS query_string FROM sqlite_master WHERE name = "%s"', static::SQLITE_TABLE_NAME))) && $data = $sth->fetch(\PDO::FETCH_ASSOC)) 
            return $data['query_string'];

        return null;
    }

    /**
    *
    */
    final public static function _showTables(?string &$query_string = null): array {
        if (($sth = static::PDO()->execute($queryString = 'SELECT name FROM sqlite_master WHERE type = "table" AND name NOT LIKE "sqlite_%"')) && $data = $sth->fetchAll(\PDO::FETCH_ASSOC)) 
            return $data;

        return [];
    }
}
?>