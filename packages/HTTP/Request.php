<?php

/**
*
*/
namespace HTTP;

/**
*
*/
class Request extends \Schema\Schema {

    /**
    *
    */
    const SCHEMA_VALUE_IS_READONLY = true;
    const SCHEMA_VALIDATE_ATTRIBUTES = [
        'Response' => self::SCHEMA_VALIDATE_IS_OBJECT_OF . __NAMESPACE__ . '\Response',
        'servers' => self::SCHEMA_VALIDATE_IS_OBJECT,
        'args' => self::SCHEMA_VALIDATE_IS_OBJECT,
        'posts' => self::SCHEMA_VALIDATE_IS_OBJECT,
        'cookies' => self::SCHEMA_VALIDATE_IS_OBJECT,
        'sessions' => self::SCHEMA_VALIDATE_IS_OBJECT,
        'attributes' => self::SCHEMA_VALIDATE_IS_SCHEMA,
    ];

    protected $http_requests = [
        '*'         => [],
        'GET'       => [],
        'POST'      => [],
        'PUT'       => [],
        'UPDATE'    => [],
        'DELETE'    => [],
        'PATCH'     => [],
        'OPTIONS'   => [],
        'CONNECT'   => [],
        'TRACE'     => [],
    ];

    /**
    *
    */
    public function __construct($Content = null, ?Response $Response = null) {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        parent::__construct([
            'Response' => $Response ?? new Response($Content),
            'servers' => (object) $_SERVER,
            'args' => (object) $_GET,
            'posts' => (object) $_POST,
            'cookies' => (object) $_COOKIE,
            'sessions' => (object) ($session_started ? $_SESSION : []),
            'attributes' => new \Schema\Schema([
                'REQUEST_METHOD' => strtoupper($_SERVER['REQUEST_METHOD']),
                'DOCUMENT_ROOT' => DOCUMENT_ROOT,
                'REQUEST_URI' => REQUEST_URI,
                'REQUEST_QUERY_URI' => REQUEST_QUERY_URI,
                'REQUEST_URIs' => explode('/', REQUEST_URI),
            ], [
                'REQUEST_METHOD' => self::SCHEMA_VALIDATE_IS_STRING,
                'DOCUMENT_ROOT' => self::SCHEMA_VALIDATE_IS_STRING,
                'REQUEST_URI' => self::SCHEMA_VALIDATE_IS_STRING,
                'REQUEST_QUERY_URI' => self::SCHEMA_VALIDATE_IS_STRING,
                'REQUEST_URIs' => self::SCHEMA_VALIDATE_IS_LIST,
            ]),
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

    /**
    *
    */
    public function map(string $methods, string $pattern, \Closure $callback): self {
        foreach(explode('|', $methods) as $method)
        	$this->http_requests[strtoupper(trim($method))][$pattern] = $callback;
 
        return $this;
    }

    /**
    *
    */
    public function execute(\Closure $callback, array $args = []) {
        $callback = \Closure::bind($callback, $this, get_class());

        foreach ((new \ReflectionFunction($callback))->getParameters() as $param)
            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
                $args[$param->getName()] = new $class_name($args[$param->getName()]);
 
        return call_user_func_array($callback, $args) || true;
    }

    /**
    *
    */
    public function listen(?string $REQUEST_URI = null, ?string $REQUEST_METHOD = null, bool $execute = true): bool {
        $REQUEST_METHOD = strtoupper($REQUEST_METHOD) ?: $this->attributes->REQUEST_METHOD;
        $REQUEST_URI = $REQUEST_URI ?: $this->attributes->REQUEST_URI;
        $REQUEST_URIs = $REQUEST_URI ? explode('/', $REQUEST_URI) : $this->attributes->REQUEST_URIs;
        
        foreach(array_replace($this->http_requests['*'], $this->http_requests[$REQUEST_METHOD]) as $pattern => $callback) {
            $args = [];
            $is_matched = true;
            $is_no_limit = false;
 
            if ($REQUEST_URI == $pattern
                || (preg_match('/^\/.+\/[a-z]*$/i', $pattern)
                        && preg_match($pattern, $REQUEST_URI, $args)))
                return $execute ? $this->execute($callback, $args) : true;
 
            foreach(array_map(
                function($value) {
                    preg_match('/(\?)?(string|int|rgex|\*)?\:([a-zA-Z0-9_]*)/is', $value, $match);
 
                    return $match
                        ? ['is_required' => $match[1] != '?', 'var_type' => $match[2], 'var_name' => $match[3], 'value' => null]
                            : ['is_required' => true, 'var_type' => null, 'var_name' => $value, 'value' => $value];
                }, explode('/', $pattern)) as $i => $options) {
                    $value = $REQUEST_URIs[$i] ?? null;
 
                    if ($options['is_required']) {
                        if (!($is_matched = !is_null($value)))
                            break;
                        else if (!$options['var_type'] && !($is_matched = $options['value'] == $value))
                            break;
                    } else if (!$options['is_required'] && is_null($args[$options['var_name']] = $value))
                        continue;
 
                    if ($is_no_limit = (strtolower($options['var_type']) == '*'))
                        $value = implode('/', array_slice($REQUEST_URIs, $i));
                    else if (strtolower($options['var_type']) == 'string' && !($is_matched = is_string($value)))
                        break;
                    else if (strtolower($options['var_type']) == 'int' && !($is_matched = is_numeric($value)))
                        break;
 
                    if ($options['var_type'])
                        $args[$options['var_name']] = $value;
            }
 
            if ($is_matched && !(!$is_no_limit && substr_count($REQUEST_URI, '/') > substr_count($pattern, '/')))
                return $execute ? $this->execute($callback, $args) : true;
        }

        return false;
    }
}
?>