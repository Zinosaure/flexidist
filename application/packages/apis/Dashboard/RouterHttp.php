<?php

/**
*
*/
namespace apis\Dashboard;

/**
*
*/
class RouterHttp {

    /**
    *
    */
    public function __construct(\RouterHttp &$RouterHttp, ?string $prefix = 'dashboard') {
        $prefix = trim($prefix, '/');
        
        $RouterHttp->map('get', $prefix . '/public/*:filename', function(string $filename) {
            if (!file_exists($filename = __DIR__ . '/public/' . $filename) || !is_file($filename)) {
                $this->Response->status_code = 404;

                return $this->Response->send();
            }

            $mime_type = 'application/octet-stream';
                
            if (array_key_exists($extension = strtolower(@array_pop(explode('.', $filename))), $mime_types = require_once FLEXIDIST_APPLICATION_PATH . 'packages/RouterHttp/assets/mime_types.php'))
                $mime_type = $mime_types[$extension];
            
            $this->Response->headers->{'Content-Type'} = $mime_type;
            $this->Response->Content = @file_get_contents($filename);
            
            return $this->Response->send();
        });

        $RouterHttp->map('get', $prefix, function() use($prefix) {
            $this->Response->Content = new \Template(__DIR__ . '\application\public_html\index.phtml', [
                'html' => [
                    'head' => [
                        'title' => null,
                        'base_href' => SERVER_NAME . DOCUMENT_ROOT . $prefix . '/'
                    ],
                    'h1' => 'Dashboard',
                    'content' => null,
                ]
            ]);

            $this->Response->send();
        });

        $RouterHttp->map('get', $prefix . '/hello', function() {
            var_dump('s');
        });
    }
}
?>