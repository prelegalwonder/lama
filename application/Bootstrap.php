<?php

if (version_compare(PHP_VERSION, '5.2.4') == -1) {
	die('Zend Framework requires PHP version 5.2.4 or newer. You have PHP version '.PHP_VERSION.'. Please contact your system administrator');
}

$root = realpath(dirname(__FILE__) . '/../');

if (is_file($root.'/application/config/zend_framework.ini')) {
	$zendFrameworkIni = parse_ini_file($root.'/application/config/zend_framework.ini');
	if ( array_key_exists('zend_framework_path', $zendFrameworkIni) ) {
		set_include_path($zendFrameworkIni['zend_framework_path'] . PATH_SEPARATOR . get_include_path());
	}
}

set_include_path('.' . PATH_SEPARATOR . $root.'/library' . PATH_SEPARATOR . $root.'/application/modules/default/models/' . PATH_SEPARATOR . get_include_path());

# detect is Zend Framework is accessible
$handle = fopen("Zend/Loader.php", "r", true);
if ($handle) {
	fclose($handle);
} else {
	die('Zend Framework is not found in PHP include_path. Please contact your system administrator');
}

require_once "Zend/Loader.php";

// Set up autoload.
Zend_Loader::registerAutoload();

// Prepare the front controller.
$frontController = Zend_Controller_Front::getInstance();

// Change to 'production' parameter under production environemtn
$frontController->registerPlugin(new mdb_Initializer());

// Dispatch the request using the front controller.
$frontController->dispatch();
