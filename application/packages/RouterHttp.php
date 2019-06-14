<?php

/**
*
*/
class RouterHttp extends \Schema {
	
	/**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = true;
    const SCHEMA_FIELDS = [
        'Request' => self::SCHEMA_FIELD_IS_OBJECT,
        'Response' => self::SCHEMA_FIELD_IS_OBJECT,
    ];
    
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
    public function __construct() {
        ($session_started = session_status() != PHP_SESSION_NONE) ? null : session_start();

        parent::__construct([
            'Request' => new \RouterHttp\Request(),
            'Response' => new \RouterHttp\Response(),
        ]);
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
    public function attach(string $api, string $prefix = null) {
        $classname = sprintf('apis\%s\RouterHttp', $api);

        return new $classname($this, $prefix);
    }

    /**
    *
    */
    public function dispatch(?string $http_request = null, ?string $method = null) {
    	$method = strtoupper($method) ?: $this->Request->attributes->REQUEST_METHOD;
        $http_request = $http_request ?: $this->Request->attributes->REQUEST_URI;
        $http_requests = explode('/', $http_request);
        
    	foreach(array_replace($this->methods['*'], $this->methods[$method]) as $pattern => $callback) {
            $args = [];
            $is_matched = true;
            $is_no_limit = false;
 
            if ($http_request == $pattern
                || (preg_match('/^\/.+\/[a-z]*$/i', $pattern)
                        && preg_match($pattern, $http_request, $args))) {
				$callback = \Closure::bind($callback, $this, get_class());

		        foreach ((new \ReflectionFunction($callback))->getParameters() as $param)
		            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
		                $args[$param->getName()] = new $class_name($args[$param->getName()]);
		 
		        return call_user_func_array($callback, $args);
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
                    } else if (!$options['is_required'] && is_null($args[$options['var_name']] = $value))
                        continue;
 
                    if ($is_no_limit = (strtolower($options['var_type']) == '*'))
                        $value = implode('/', array_slice($http_requests, $i));
                    else if (strtolower($options['var_type']) == 'string' && !($is_matched = is_string($value)))
                        break;
                    else if (strtolower($options['var_type']) == 'int' && !($is_matched = is_numeric($value)))
                        break;
 
                    if ($options['var_type'])
                        $args[$options['var_name']] = $value;
            }
 
            if ($is_matched && !(!$is_no_limit && substr_count($http_request, '/') > substr_count($pattern, '/'))) {
            	$callback = \Closure::bind($callback, $this, get_class());

		        foreach ((new \ReflectionFunction($callback))->getParameters() as $param)
		            if (($param_type = $param->getType()) && !in_array($class_name = $param_type->getName(), ['int', 'string']))
		                $args[$param->getName()] = new $class_name($args[$param->getName()]);
		 
		        return call_user_func_array($callback, $args);
            }
        }

        return false;
    }
}