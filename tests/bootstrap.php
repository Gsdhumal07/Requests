<?php

date_default_timezone_set('UTC');

function define_from_env($name, $default = false) {
	$env = getenv($name);
	if ($env) {
		define($name, $env);
	}
	else {
		define($name, $default);
	}
}

define_from_env('REQUESTS_TEST_HOST', 'requests-php-tests.herokuapp.com');
define_from_env('REQUESTS_TEST_HOST_HTTP', REQUESTS_TEST_HOST);
define_from_env('REQUESTS_TEST_HOST_HTTPS', REQUESTS_TEST_HOST);

define_from_env('REQUESTS_HTTP_PROXY');
define_from_env('REQUESTS_HTTP_PROXY_AUTH');
define_from_env('REQUESTS_HTTP_PROXY_AUTH_USER');
define_from_env('REQUESTS_HTTP_PROXY_AUTH_PASS');

require_once dirname(__DIR__) . '/library/Requests.php';
Requests::register_autoloader();

if (is_dir(dirname(__DIR__) . '/vendor')
	&& file_exists(dirname(__DIR__) . '/vendor/autoload.php')
	&& file_exists(dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php')
) {
	$vendor_dir = dirname(__DIR__) . '/vendor';
} else {
	echo 'Please run `composer install` before attempting to run the unit tests.
You can still run the tests using a PHPUnit phar file, but some test dependencies need to be available.
';
	die(1);
}

// Load the PHPUnit Polyfills autoloader.
require_once $vendor_dir . '/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// New autoloader.
spl_autoload_register(
	function ($class_name) {
		// Only try & load our own classes.
		if (stripos($class_name, 'Requests\\Tests\\') !== 0) {
			return false;
		}

		// Strip namespace prefix 'Requests\Tests\'.
		$relative_class = substr($class_name, 15);
		$file           = realpath(__DIR__ . '/' . strtr($relative_class, '\\', '/') . '.php');

		if (file_exists($file)) {
			include_once $file;
		}

		return true;
	}
);

// Old autoloader.
function autoload_tests($class) {
	if (strpos($class, 'RequestsTest_') !== 0) {
		return;
	}

	$class = substr($class, 13);
	$file  = str_replace('_', '/', $class);
	if (file_exists(__DIR__ . '/' . $file . '.php')) {
		require_once __DIR__ . '/' . $file . '.php';
	}
}

spl_autoload_register('autoload_tests');

function httpbin($suffix = '', $ssl = false) {
	$host = $ssl ? 'https://' . REQUESTS_TEST_HOST_HTTPS : 'http://' . REQUESTS_TEST_HOST_HTTP;
	return rtrim($host, '/') . '/' . ltrim($suffix, '/');
}
