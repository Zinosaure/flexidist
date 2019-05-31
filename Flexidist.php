<?php

/**
 *
 */
spl_autoload_register(function(string $classname) {
	foreach ([
		__DIR__ . '/packages/',
		APPLICATION_PATH . 'packages/',
	] as $namespace)
		if (is_readable($filename = $namespace . str_replace('\\', '/', $classname) . '.php'))
			return require_once $filename;

	return false;
});

session_start();

foreach([
	'IS_LOCALHOST' =>
		$is_localhost ?? false,
	'IS_HTTPS' =>
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off',
	'IS_REQUEST_METHOD_AJAX' =>
		isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest',
	'IS_REQUEST_METHOD_GET' =>
		strtolower($_SERVER['REQUEST_METHOD']) == 'get',
	'IS_REQUEST_METHOD_POST' =>
		strtolower($_SERVER['REQUEST_METHOD']) == 'post',
	
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
	'TEMPLATES_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/templates/',
	'PACKAGES_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/packages/',
] as $name => $value)
if (!defined($name))
	define(strtoupper($name), $value);
	
foreach ([
	APPLICATION_PATH,
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

	ini_set('html_errors', true);
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('ignore_repeated_errors', false);
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