<?php

/**
*
*/
require_once '../flexidist/Flexidist.php';
$Request = new \ServerHttp\Request(current(explode('/', trim(REQUEST_URI, '/'))), __DIR__);

$Request->Response->Content = new \ServerHttp\ResponseContent\TemplateEngine(__DIR__ . '/application/templates/index.phtml');
$Request->Response->Content->arg('html', [
    'head' => [
        'title' => 'Dashboard',
        'base_href' => $Request->attributes->SERVER_DOCUMENT_ROOT,
    ],
    'h1' => 'Dashboard',
    'content' => file_get_contents(__DIR__ . '/application/templates/contents/index.phtml'),
]);
$Request->map('*', '', function() {
    echo $this->Response;
});

$Request->listen();
?>