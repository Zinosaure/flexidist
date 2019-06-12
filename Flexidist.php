<?php

/**
*
*/
declare(strict_types=1);
session_start();

define('ROOT_PACKAGES_PATH', __DIR__ . '/application/packages/');
define('ROOT_MODULES_PATH', __DIR__ . '/application/modules/');

spl_autoload_register(function(string $classname) {
	foreach ([ROOT_PACKAGES_PATH, PACKAGES_PATH] as $namespace)
		if (is_readable($filename = $namespace . str_replace('\\', '/', $classname) . '.php'))
			return require_once $filename;

	return false;
});

foreach([
	'IS_LOCALHOST' =>
		$is_localhost ?? in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']),
	'IS_HTTPS' =>
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off',
	
	'SERVER_NAME' =>
		(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'],
	'REMOTE_IP_ADDRESS' =>
		$_SERVER['REMOTE_ADDR'] ?? null,
	'DOCUMENT_ROOT' =>
		(($document_root = dirname($_SERVER['SCRIPT_NAME'])) && strlen($document_root) > 1 && $document_root != '/') ? $document_root . '/' : $document_root,
	'REQUEST_URI' =>
		substr(str_replace($document_root, ($document_root != '/') ? null : '/', preg_replace('/\?(.*)?/is', null, $_SERVER['REQUEST_URI'])), 1),
	'REQUEST_QUERY_URI' =>
		substr(str_replace($document_root, ($document_root != '/') ? null : '/', $_SERVER['REQUEST_URI']), 1),

	'SCRIPT_DIRECTORY_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/',
	'APPLICATION_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/',
	'MODULES_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/modules/',
	'PACKAGES_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/packages/',
	'TEMPLATES_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/templates/',
] as $name => $value)
	if (!defined($name))
		define(strtoupper($name), $value);
	
foreach ([
	APPLICATION_PATH,
	APPLICATION_PATH . 'modules/',
	APPLICATION_PATH . 'packages/',
	APPLICATION_PATH . 'templates/',
	APPLICATION_PATH . '../public/',
	APPLICATION_PATH . '../public/css-js/',
	APPLICATION_PATH . '../public/images/',
] as $dirname) {
	if (!is_dir($dirname)) {
		@mkdir($dirname, 0777, true);
		@chmod($dirname, 0777);
	}
}

if (IS_LOCALHOST) {
	error_reporting(E_ALL);

	ini_set('html_errors', 'On');
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('ignore_repeated_errors', 'Off');
} else {
	error_reporting(E_ALL);

	ini_set('display_errors', 'Off');
	ini_set('log_errors', 'On');
	ini_set('error_log', APPLICATION_PATH . 'php_errors.log');
}

if (!file_exists($filename = APPLICATION_PATH . '../.htaccess')) {
	file_put_contents($filename, implode("\n", [
		'RewriteEngine on',
		'RewriteBase ' . DOCUMENT_ROOT,
		'',
		'RewriteCond %{REQUEST_FILENAME} !-f',
		'RewriteRule .* index.php',
	]));
	@chmod($filename, 0777);
}


if (!file_exists($filename = APPLICATION_PATH . '.htaccess')) {
	file_put_contents($filename, 'deny from all');
	@chmod($filename, 0777);
}

/*
if (IS_LOCALHOST && !preg_match('/^www\./is', $_SERVER['SERVER_NAME']))
	return header('Location: //www.' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], true, 301);
else if (preg_match('/index\.(php|html|htm)$/is', $_SERVER['REQUEST_URI']))
	return header('Location: ' . SERVER_NAME . DOCUMENT_ROOT, true, 301);
*/
?>