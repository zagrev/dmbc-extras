<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function dmbc_extras_uninstall() {

	\add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'dmbc-extras has been uninstalled.', 'dmbc-extras' ) . '</p></div>';
		}
	);
}
