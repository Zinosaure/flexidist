<?php

/**
*
*/
require_once '../flexidist/Flexidist.php';

use \ServerHttp\ResponseContent\TemplateEngine as T;

$Request = new \ServerHttp\Request(current(explode('/', trim(REQUEST_URI, '/'))), __DIR__);

$Request->Response->Content = new T(__DIR__ . '/application/templates/index.phtml');
$Request->Response->Content->arg('html', [
    'head' => [
        'title' => 'Dashboard',
        'base_href' => $Request->attributes->SERVER_DOCUMENT_ROOT,
    ],
    'h1' => 'Dashboard',
    'content' => null,
]);

$Request->map('get', '/^(|contents)$/', function() {
    $this->Response->Content->arg('html.content', new T(__DIR__ . '/application/templates/contents/index.phtml'));

    echo $this->Response;
});

$Request->map('get', 'contents/publish', function() {
    $this->Response->Content->arg('html.content', new T(__DIR__ . '/application/templates/contents/publish.phtml'));

    echo $this->Response;
});

$Request->listen();
?>