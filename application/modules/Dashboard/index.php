<?php

/**
*
*/
$Request = new ServerHttp\Request(true);
ServerHttp\ResponseContent\HTML::$TEMPLATES_PATH = $TEMPLATES_PATH;

$Request->Response->Content = new ServerHttp\ResponseContent\HTML('index.phtml', [
    'html' => [
        'h1' => 'Lorem Ipsum',
        'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sem libero, scelerisque eu maximus et, iaculis luctus odio.',
    ]
]);

$Request->map('get', 'contents', function() {
    $this->Response->Content->args['html']['content'] = new ServerHttp\ResponseContent\HTML('contents/index.phtml');
    
    $this->Response->send();
});

$Request->listen();
?>