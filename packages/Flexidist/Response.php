<?php

/**
*
*/
namespace Flexidist;

/**
*
*/
class Response {

    /**
    *
    */
    use \traits\dotnotation;
    
    public $Content = null;

    /**
    *
    */
    public function __construct(?\Flexidist\Response\Content &$Content = null, array $options = []) {
        $this->Content = $Content ?? new \Flexidist\Response\Content();
        $this->dn_init(array_replace_recursive([
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            'cookies' => [],
            'status_code' => 200,
            'protocol_version' => '1.1',
        ], $options));
    }

    /**
    *
    */
    public function send(int $status_code = null, bool $is_informational = false, bool $evaluate = true) {
        $status_code = $status_code ?? $this->dn_get('status_code');
        http_response_code($status_code);

        if (!headers_sent()) {
            foreach ($this->dn_get('headers') as $name => $value)
                header(sprintf('%s: %s', $name, $value), 0 === strcasecmp($name, 'Content-Type'), $status_code);
            
            foreach ($this->dn_get('cookies') as $name => $value)
                header(sprintf('Set-Cookie: %s=%s', $name, $value), false, $status_code);
        }
        
        return !$is_informational ? $this->Content->output($evaluate) : null;
    }
}
?>