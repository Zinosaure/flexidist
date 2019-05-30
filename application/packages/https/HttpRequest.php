<?php

/**
*
*/
namespace https;

/**
*
*/
class HttpRequest extends HttpResponse {

    /**
     *
     */
    protected $HttpResponse = null;

    /**
     *
     */
    public function __construct(HttpResponse &$HttpResponse) {
        $this->HttpResponse = $HttpResponse;
    }

    /**
    *
    */
    final public function bind(\Closure $callback, array $callback_args = []): bool {
        $this->HttpResponse->callback = $callback;
        $this->HttpResponse->callback_args = $callback_args;

        return true;
    }

    /**
     *
     */
    final public function listen(string $request_url = null, string $method = null): bool {
        if (!in_array($method = strtoupper($method ?: $_SERVER['REQUEST_METHOD']), array_keys($this->HttpResponse->http_responses)))
            return false;

        $request_url_exploded = explode('/', preg_replace('/\?(.*)?/is', null, $_SERVER['REQUEST_URI']));
        $request_url_exploded = array_splice($request_url_exploded, count(explode('/', dirname($_SERVER['SCRIPT_NAME']))));
        $request_url = $request_url ?: implode('/', $request_url_exploded);

        foreach(array_replace($this->HttpResponse->http_responses['*'], $this->HttpResponse->http_responses[$method]) as $request_pattern => $callback) {
            $callback_args = [];
            $is_matched = true;
            $is_no_limit = false;

            if ($request_url == $request_pattern
                || (preg_match('/^\/.+\/[a-z]*$/i', $request_pattern)
                        && preg_match($request_pattern, $request_url, $callback_args)))
                return $this->bind($callback, $callback_args);

            foreach(array_map(
                function($value) {
                    preg_match('/(\?)?(string|int|rgex|\*)?\:([a-zA-Z0-9_]*)/is', $value, $match);

                    return $match
                        ? ['is_required' => $match[1] != '?', 'var_type' => $match[2], 'var_name' => $match[3], 'value' => null]
                            : ['is_required' => true, 'var_type' => null, 'var_name' => $value, 'value' => $value];
                }, explode('/', $request_pattern)) as $i => $options) {
                    $value = $request_url_exploded[$i] ?? null;

                    if ($options['is_required']) {
                        if (!($is_matched = !is_null($value)))
                            break;
                        else if (!$options['var_type'] && !($is_matched = $options['value'] == $value))
                            break;
                    } else if (!$options['is_required'] && is_null($callback_args[$options['var_name']] = $value))
                        continue;

                    if ($is_no_limit = (strtolower($options['var_type']) == '*'))
                        $value = implode('/', array_slice($request_url_exploded, $i));
                    else if (strtolower($options['var_type']) == 'string' && !($is_matched = is_string($value)))
                        break;
                    else if (strtolower($options['var_type']) == 'int' && !($is_matched = is_numeric($value)))
                        break;

                    if ($options['var_type'])
                        $callback_args[$options['var_name']] = $value;
            }

            if ($is_matched && !(!$is_no_limit && substr_count($request_url, '/') > substr_count($request_pattern, '/')))
                return $this->bind($callback, $callback_args);
        }

        return false;
    }
}
?>