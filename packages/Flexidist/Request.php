<?php

/**
*
*/
namespace Flexidist;

/**
*
*/
class Request {

    /**
    *
    */
    use \traits\dotnotation;

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
        'TRACE'   => [],
    ];
    protected $Response = null;

    /**
    *
    */
    public function __construct($Content = null, ?Response $Response = null) {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        $this->Response = $Response ?? new Response($Content);

        $this->dn_init([
            'server' => $_SERVER,
            'arg' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'session' => $session_started ? $_SESSION : [],
            'attributes' => [
                'REQUEST_METHOD' => strtoupper($_SERVER['REQUEST_METHOD']),
                'DOCUMENT_ROOT' => DOCUMENT_ROOT,
                'REQUEST_URI' => REQUEST_URI,
                'REQUEST_QUERY_URI' => REQUEST_QUERY_URI,
                'REQUEST_URIs' => explode('/', REQUEST_URI),
            ],
        ]);
    }

    /**
    *
    */
    public static function fetch(string $request_uri, array $options = []) {
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
        $REQUEST_METHOD = strtoupper($REQUEST_METHOD) ?: $this->dn_get('attributes.REQUEST_METHOD');
        $REQUEST_URI = $REQUEST_URI ?: $this->dn_get('attributes.REQUEST_URI');
        $REQUEST_URIs = $REQUEST_URI ? explode('/', $REQUEST_URI) : $this->dn_get('attributes.REQUEST_URIs');
        
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

    /**
    *
    */
    public function send(int $status_code = null) {
        $this->Response->send($status_code);
    }
}
?>