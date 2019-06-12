<?php

/**
*
*/
$Request = new ServerHttp\Request(true);
ServerHttp\ResponseContent\HTML::$TEMPLATES_PATH = $TEMPLATES_PATH;

$Request->Response->Content = new ServerHttp\ResponseContent\HTML('index.phtml');

$Request->map('get', 'hello', function() {
    $this->Response->send();
});

$Request->listen();
?>