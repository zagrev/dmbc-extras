<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_deactivate() {
	// Flush rewrite rules to remove the custom post type's rewrite rules
	flush_rewrite_rules();
}
