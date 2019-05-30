<?php

/**
*
*/
namespace https;

/**
*
*/
class HttpResponse  {

    /**
    *
    */
    protected $OutputHTML = null;
    protected $http_responses = [
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
    protected $bad_http_responses = [
        401 => null,
        403 => null,
        404 => null,
    ];
    protected $callback = null;
    protected $callback_args = [];

    /**
     *
     */
    public function __construct(\outputs\OutputHTML &$OutputHTML = null) {
        $this->OutputHTML = $OutputHTML ?: new OutputHTML();

        foreach ($this->bad_http_responses as $status_code => $callback)
            $this->bad_http_responses[$status_code] = function(string $status_code) { http_response_code($status_code); };
    }

    /**
     *
     */
    public function home(\Closure $callback): self {
        $this->http_responses['*'][null] = $callback;

        return $this;
    }

    /**
     *
     */
    public function error(int $status_code, \Closure $callback): self {
        $this->bad_http_responses[$status_code] = $callback;

        return $this;
    }

    /**
     *
     */
    public function get(string $pattern, \Closure $callback): self {
        $this->http_responses['GET'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function post(string $pattern, \Closure $callback): self {
        $this->http_responses['POST'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function put(string $pattern, \Closure $callback): self {
        $this->http_responses['PUT'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function update(string $pattern, \Closure $callback): self {
        $this->http_responses['UPDATE'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function delete(string $pattern, \Closure $callback): self {
        $this->http_responses['DELETE'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function patch(string $pattern, \Closure $callback): self {
        $this->http_responses['PATCH'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function options(string $pattern, \Closure $callback): self {
        $this->http_responses['OPTIONS'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function connect(string $pattern, \Closure $callback): self {
        $this->http_responses['CONNECT'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function all(string $pattern, \Closure $callback): self {
        $this->http_responses['*'][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    public function map(string $methods, string $pattern, \Closure $callback): self {
        foreach(explode('|', $methods) as $method)
            if (in_array($method = strtoupper($method), array_keys($this->pattern_callbacks)))
                $this->http_responses[$method][$pattern] = $callback;

        return $this;
    }

    /**
     *
     */
    final public function execute(int $status_code = 200) {
        if ($status_code > 200)
            $this->callback = $this->bad_http_responses[$status_code];

        $callback = \Closure::bind($this->callback, $this->OutputHTML);
        $callback_args = $this->callback_args;

        foreach ((new ReflectionFunction($callback))->getParameters() as $param)
            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
                $callback_args[$param->getName()] = new $class_name($callback_args[$param->getName()]);

        return call_user_func_array($callback, $callback_args);
    }
}
?>