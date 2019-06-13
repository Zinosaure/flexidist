<?php

/**
*
*/
namespace ServerHttp;

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
    public function __construct(?string $next_root = null, string $public_dirname = null) {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        parent::__construct([
            'servers' => (object) $_SERVER,
            'args' => (object) $_GET,
            'posts' => (object) $_POST,
            'cookies' => (object) $_COOKIE,
            'sessions' => (object) ($session_started ? $_SESSION : []),
            'attributes' => [
                'REQUEST_METHOD' => strtoupper($_SERVER['REQUEST_METHOD']),
                'DOCUMENT_ROOT' => $DOCUMENT_ROOT = str_replace('//', '/', DOCUMENT_ROOT . ($next_root = trim($next_root, '/') . '/')),
                'REQUEST_URI' => $REQUEST_URI = preg_replace('/^' . preg_quote($next_root, '/') . '/', null, REQUEST_URI),
                'REQUEST_QUERY_URI' => $REQUEST_QUERY_URI = preg_replace('/^' . preg_quote($next_root, '/') . '/', null, REQUEST_QUERY_URI),
                'SERVER_DOCUMENT_ROOT' => SERVER_NAME . $DOCUMENT_ROOT,
                'FULL_REQUEST_URI' => SERVER_NAME . $DOCUMENT_ROOT . $REQUEST_URI,
                'FULL_REQUEST_QUERY_URI' => SERVER_NAME . $DOCUMENT_ROOT . $REQUEST_QUERY_URI,
                'REQUEST_URIs' => explode('/', trim($REQUEST_URI, '/')),
            ],
        ]);

        if ($public_dirname)
            $this->mapPublicResources($public_dirname);
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

    

    /**
    *
    */
    public function mapPublicResources(string $dirname) {
        $this->map('get', 'public/*:filename', function(string $filename) use ($dirname) {
            if (!file_exists($filename = $dirname . '/public/' . $filename) || !is_file($filename)) {
                $this->Response->status_code = 404;

                return $this->Response->send();
            }

            $mime_type = 'application/octet-stream';
                
            if (array_key_exists($extension = strtolower(@array_pop(explode('.', $filename))), $mime_types = require_once __DIR__ . '/mime_types.php'))
                $mime_type = $mime_types[$extension];
            
            $this->Response->headers->{'Content-Type'} = $mime_type;
            $this->Response->Content = @file_get_contents($filename);
            
            return $this->Response->send();
        });
    }

    /**
    *
    */
    public function mapForwardRequest(string $methods, string $pattern, string $contrib_name): self {
        return $this->map($methods, $pattern, function() use ($contrib_name) {
            foreach ([APPLICATION_PATH . 'maps', FLEXIDIST_APPLICATION_PATH . 'maps'] as $contrib_space) {
                if (is_readable($filename = sprintf('%s/%s/index.php', $contrib_space, $contrib_name))) {
                    spl_autoload_register(function(string $classname) use ($filename) {
                        if (is_readable($filename = sprintf('%s/application/packages/%s.php', dirname($filename), str_replace('\\', '/', $classname))))
                            return require_once $filename;
                    
                        return false;
                    });
    
                    return require_once $filename;
                }
            }
        
            return false;
        });
    }
}
?>