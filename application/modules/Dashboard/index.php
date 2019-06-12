<?php

/**
*
*/
$Request = HTTP\Request::create();
$Request->Response->Content = new \HTTP\ResponseContent\HTML();

$Request->map('get', 'hello', function() {
    $this->Response->send();
});

$Request->listen();
?>