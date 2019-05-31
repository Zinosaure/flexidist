<?php

namespace Flexidist;

class Request {

    use \traits\dotnotation;
    public $Response = null;
    protected $accept_methods = [
        '*',
        'GET',
        'POST',
        'PUT',
        'UPDATE',
        'DELETE',
        'PATCH',
        'OPTIONS',
        'CONNECT',
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
    ];

    public function __construct(Response &$Response = null) {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        $this->Response = $Response ?? new Response();
        $this->dn_init([
            'server' => $_SERVER,
            'arg' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'session' => $session_started ? $_SESSION : [],
            'attributes' => [
                'full_request' => $full = explode('/', trim(preg_replace('/\?(.*)?/is', null, $_SERVER['REQUEST_URI']), '/')),
                'request' => array_splice($full, count(explode('/', dirname($_SERVER['SCRIPT_NAME']))) - 1)
            ],
        ]);
    }

    public static function send(string $request_uri, array $options = []) {
        return @file_get_contents($request_uri, false, stream_context_create(array_replace_recursive([
            'http' => [
                'method' => 'GET',
            ]
        ], $options)));
    }

    public static function redirectTo(string $url, int $status_code = 301) {
        exit(header('Location: ' . $url, false, $status_code));
    }

    public function map(string $methods, string $pattern, \Closure $callback): self {
        foreach(explode('|', $methods) as $method)
            if (in_array($method = strtoupper($method), $this->accept_methods))
                $this->http_requests[$method][$pattern] = $callback;
 
        return $this;
    }

    public function listen(?string $request_url = null, ?string $method = null, bool $execute = true): bool {
        if (!in_array($method = strtoupper($method ?: $this->dn_get('server.REQUEST_METHOD')), $this->accept_methods))
            return false;

        $attr_requests = $request_url ? explode('/', $request_url) : $this->dn_get('attributes.request');
        $request_url = implode('/', $attr_requests);
        
        foreach(array_replace($this->http_requests['*'], $this->http_requests[$method]) as $pattern => $callback) {
            $args = [];
            $is_matched = true;
            $is_no_limit = false;
 
            if ($request_url == $pattern
                || (preg_match('/^\/.+\/[a-z]*$/i', $pattern)
                        && preg_match($pattern, $request_url, $args)))
                return $execute ? $this->execute($callback, $args) : true;
 
            foreach(array_map(
                function($value) {
                    preg_match('/(\?)?(string|int|rgex|\*)?\:([a-zA-Z0-9_]*)/is', $value, $match);
 
                    return $match
                        ? ['is_required' => $match[1] != '?', 'var_type' => $match[2], 'var_name' => $match[3], 'value' => null]
                            : ['is_required' => true, 'var_type' => null, 'var_name' => $value, 'value' => $value];
                }, explode('/', $pattern)) as $i => $options) {
                    $value = $attr_requests[$i] ?? null;
 
                    if ($options['is_required']) {
                        if (!($is_matched = !is_null($value)))
                            break;
                        else if (!$options['var_type'] && !($is_matched = $options['value'] == $value))
                            break;
                    } else if (!$options['is_required'] && is_null($args[$options['var_name']] = $value))
                        continue;
 
                    if ($is_no_limit = (strtolower($options['var_type']) == '*'))
                        $value = implode('/', array_slice($attr_requests, $i));
                    else if (strtolower($options['var_type']) == 'string' && !($is_matched = is_string($value)))
                        break;
                    else if (strtolower($options['var_type']) == 'int' && !($is_matched = is_numeric($value)))
                        break;
 
                    if ($options['var_type'])
                        $args[$options['var_name']] = $value;
            }
 
            if ($is_matched && !(!$is_no_limit && substr_count($request_url, '/') > substr_count($pattern, '/')))
                return $execute ? $this->execute($callback, $args) : true;
        }

        return false;
    }

    public function execute(\Closure $callback, array $args = []) {
        $callback = \Closure::bind($callback, $this, get_class());

        foreach ((new ReflectionFunction($callback))->getParameters() as $param)
            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
                $args[$param->getName()] = new $class_name($args[$param->getName()]);
 
        return call_user_func_array($callback, $args) || true;
    }
}
?>