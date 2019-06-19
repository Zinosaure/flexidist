<?php

/**
*
*/
final class RouterHttp extends \Schema {
	
	/**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = true;
    const SCHEMA_FIELDS = [
        'Request' => self::SCHEMA_FIELD_IS_OBJECT,
        'Response' => self::SCHEMA_FIELD_IS_OBJECT,
        'SecurityControl' => self::SCHEMA_FIELD_IS_OBJECT,
    ];
    
    protected $callback = null;
    protected $callback_args = [];
    protected $methods = [
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
    public function __construct(\RouterHttp\SecurityControl $SecurityControl = null) {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        parent::__construct([
            'Request' => new \RouterHttp\Request(),
            'Response' => new \RouterHttp\Response(),
            'SecurityControl' => $SecurityControl,
        ]);
    }

    /**
    *
    */
    public function checkpoint(int $checkpoint_level) {
        if ($this->SecurityControl)
            return $this->SecurityControl->checkpoint($checkpoint_level, $this);
    }

    /**
    *
    */
    public function isRestricted() {
        if ($this->SecurityControl)
            return $this->SecurityControl->isRestricted($this);
    }
    
    /**
    *
    */
    public function map(string $methods, string $pattern, \Closure $callback): self {
        foreach(explode('|', $methods) as $method)
        	$this->methods[strtoupper(trim($method))][$pattern] = $callback;
 
        return $this;
    }

    /**
    *
    */
    public function execute(\Closure $callback = null, array $callback_args = []) {
        $callback = $callback ?: $this->callback;
        $callback_args = $callback_args ?: $this->callback_args;

        if (is_null($callback))
            $callback = function() { http_response_code(404); };

        foreach ((new \ReflectionFunction($callback))->getParameters() as $param)
            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
                $callback_args[$param->getName()] = new $class_name($callback_args[$param->getName()]);
 
        return $callback->call($this, ...array_values($callback_args));
    }

    /**
    *
    */
    public function dispatch(?string $http_request = null, ?string $method = null): bool {
    	$method = strtoupper($method) ?: $this->Request->attributes->REQUEST_METHOD;
        $http_request = $http_request ?: $this->Request->attributes->REQUEST_URI;
        $http_requests = explode('/', $http_request);
        
    	foreach(array_replace($this->methods['*'], $this->methods[$method]) as $pattern => $callback) {
            $callback_args = [];
            $is_matched = true;
            $is_no_limit = false;
 
            if ($http_request == $pattern
                || (preg_match('/^\/.+\/[a-z]*$/i', $pattern)
                        && preg_match($pattern, $http_request, $callback_args))) {
                $this->callback = $callback;
                $this->callback_args = $callback_args;

                return true;
			}
 
            foreach(array_map(
                function($value) {
                    preg_match('/(\?)?(string|int|regex|\*)?\:([a-zA-Z0-9_]*)/is', $value, $match);
 
                    return $match ? [
                    	'is_required' => $match[1] != '?',
                    	'var_type' => $match[2],
                    	'var_name' => $match[3],
                    	'value' => null
                    ] : [
                    	'is_required' => true,
                    	'var_type' => null,
                    	'var_name' => $value,
                    	'value' => $value
                    ];
                }, explode('/', $pattern)) as $i => $options) {
                    $value = $http_requests[$i] ?? null;
 
                    if ($options['is_required']) {
                        if (!($is_matched = !is_null($value)))
                            break;
                        else if (!$options['var_type'] && !($is_matched = $options['value'] == $value))
                            break;
                    } else if (!$options['is_required'] && is_null($callback_args[$options['var_name']] = $value))
                        continue;
 
                    if ($is_no_limit = (strtolower($options['var_type']) == '*'))
                        $value = implode('/', array_slice($http_requests, $i));
                    else if (strtolower($options['var_type']) == 'string' && !($is_matched = is_string($value)))
                        break;
                    else if (strtolower($options['var_type']) == 'int' && !($is_matched = is_numeric($value)))
                        break;
 
                    if ($options['var_type'])
                        $callback_args[$options['var_name']] = $value;
            }
 
            if ($is_matched && !(!$is_no_limit && substr_count($http_request, '/') > substr_count($pattern, '/'))) {
            	$this->callback = $callback;
                $this->callback_args = $callback_args;

                return true;
            }
        }

        return false;
    }
}