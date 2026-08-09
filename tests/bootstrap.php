<?php
namespace dmbc_extras\Tests;

use Brain\Monkey;

// Ensure PHPUnit is installed
if ( ! defined( 'PHPUNIT_COMPOSER_INSTALL' ) && ! \defined( '__PHPUNIT_PHAR__' ) ) {
	exit( 'PHPUnit is required to run these tests.' );
}

// Load the Composer autoloader
global $plugin_dir;
$plugin_dir = dirname( __DIR__ );

print 'Initializing vendor packages...' . PHP_EOL;
require_once $plugin_dir . '/vendor/autoload.php';

// Define minimal WordPress constants often used in plugins
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( dirname( dirname( $plugin_dir ) ) ) );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( dirname( $plugin_dir ) ) );
}


// Load WordPress mock functions
print 'Loading WordPress mock functions...' . PHP_EOL;
require_once "$plugin_dir/tests/DmbcMocks.php";
require_once "$plugin_dir/tests/DmbcTestCase.php";

// Load plugin files
print 'Loading dmbc-extras files...' . PHP_EOL;
require_once "$plugin_dir/dmbc-extras.php";
