<?php
// Step 1: Prevent execution if not running inside PHPUnit
if ( ! defined( 'PHPUNIT_COMPOSER_INSTALL' ) && ! defined( '__PHPUNIT_PHAR__' ) ) {
	exit( 'PHPUnit is required to run these tests.' );
}

// Step 2: Load the Composer autoloader
$base_dir = dirname( __DIR__ );
if ( file_exists( $base_dir . '/vendor/autoload.php' ) ) {
	require_once $base_dir . '/vendor/autoload.php';
} else {
	exit( 'Please run "composer install" before running tests.' );
}

// Step 3: Define minimal WordPress constants often used in plugins
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $base_dir . '/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content/' );
}

require_once dirname( __DIR__ ) . '/dmbc-extras.php';
