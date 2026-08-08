<?php
namespace dmbc_extras;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function dmbc_extras_deactivate() {
	// Flush rewrite rules to remove the custom post type's rewrite rules
	flush_rewrite_rules();
}
