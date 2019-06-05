<?php

/**
*
*/
namespace HttpStatus;

/**
*
*/
class Response {

    /**
    *
    */
    use \traits\dotnotation;

    const HTTP_STATUS_TEXTS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    protected $Content = null;
    
    /**
    *
    */
    public function __construct(&$Content = null, int $status_code = 200, array $headers = []) {
        $this->setContent($Content);
        $this->dn_init([
            'status_code' => $status_code,
            'headers' => array_replace([
                'Content-Type' => 'text/html; charset=utf-8',
            ], $headers),
            'set_cookies' => [],
        ]);
    }

    /**
    *
    */
    public function __get(string $name) {
        return $this->{$name};
    }

    /**
    *
    */
    public function __set(string $name, $mixed_value) {
        if ($name === 'Content')
            return $this->setContent($mixed_value);

        return $this->{$name} = $mixed_value;
    }

    /**
    *
    */
    public function setContent($content) {
        if (!is_null($content) && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString']))
            throw new \UnexpectedValueException(sprintf('The %s::Content must be a string or object implementing __toString(), "%s" given.', get_called_class(), gettype($content)));

        if (!is_null($content) && preg_match('/\.phtml$/isU', $content) && is_file(TEMPLATES_PATH . $content))
            $content = file_get_contents(TEMPLATES_PATH . $content);

        $this->Content = $content;
    }

    /**
    *
    */
    public function send(int $status_code = null) {
        $status_code = $status_code ?? $this->dn_get('status_code');

        if (!headers_sent()) {
            foreach ($this->dn_get('headers') as $name => $value)
                header(sprintf('%s: %s', $name, $value), 0 === strcasecmp($name, 'Content-Type'), $status_code);
            
            foreach ($this->dn_get('set_cookies') as $name => $value)
                header(sprintf('Set-Cookie: %s=%s', $name, $value), false, $status_code);
            
            http_response_code($status_code);
        }

        echo (string) $this->Content;
    }
}
?>