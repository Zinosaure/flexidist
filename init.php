<?php

/**
*
*/
declare(strict_types=1);
session_start();

spl_autoload_register(function(string $classname) {
	$namespace = SRC_PACKAGES_PATH;
	
	if (preg_match('/^packages/is', $classname))
		$namespace = APPLICATION_PATH;

	if (file_exists($filename = $namespace . str_replace('\\', '/', $classname) . '.php'))
		return require_once $filename;

	return false;
});

foreach([
	'IS_LOCALHOST' =>
		isset($is_localhost) && is_bool($is_localhost) ? $is_localhost : in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']),
	'IS_HTTPS' =>
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off',
	
	'SERVER_NAME' =>
		(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'],
	'DOCUMENT_ROOT' =>
		(($DOCUMENT_ROOT = dirname($_SERVER['SCRIPT_NAME'])) && strlen($DOCUMENT_ROOT) > 1 && $DOCUMENT_ROOT != '/') ? $DOCUMENT_ROOT . '/' : $DOCUMENT_ROOT,
	'REQUEST_URI' =>
		$REQUEST_URI = substr(str_replace($DOCUMENT_ROOT, ($DOCUMENT_ROOT != '/') ? null : '/', preg_replace('/\?(.*)?/is', null, $_SERVER['REQUEST_URI'])), 1),
	'REQUEST_QUERY_URI' =>
		substr(str_replace($DOCUMENT_ROOT, ($DOCUMENT_ROOT != '/') ? null : '/', $_SERVER['REQUEST_URI']), 1),

	'SRC_PACKAGES_PATH' =>
		__DIR__ . '/packages/',
	'APPLICATION_PATH' =>
		dirname($_SERVER['SCRIPT_FILENAME']) . '/application/',
] as $name => $value)
	if (!defined($name))
		define(strtoupper($name), $value);

foreach ([
	APPLICATION_PATH,
	APPLICATION_PATH . 'packages/',
	APPLICATION_PATH . 'packages/public_html/',
	APPLICATION_PATH . '../public/',
] as $dirname) {
	if (is_dir($dirname))
		break;
	
	@mkdir($dirname, 0777, true);
	@chmod($dirname, 0777);
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

if (!IS_LOCALHOST && !preg_match('/^www\./is', $_SERVER['SERVER_NAME']))
	return header('Location: //www.' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], true, 301);
else if (preg_match('/index\.(php|html|htm)$/is', $_SERVER['REQUEST_URI']))
	return header('Location: ' . SERVER_NAME . DOCUMENT_ROOT, true, 301);
?>