<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_uninstall() {

	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'dmbc-extras has been uninstalled.', 'dmbc-extras' ) . '</p></div>';
		}
	);
}
