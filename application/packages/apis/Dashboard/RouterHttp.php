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

        

        $RouterHttp->map('get', $prefix, function() use($prefix) {
            $this->Response->Content = new \Template(__DIR__ . '/application/public_html/index.phtml', [
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