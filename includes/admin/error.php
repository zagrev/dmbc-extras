<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

/**
 * Displays an admin error and removes its query argument from the referrer.
 *
 * @param string $message  Error message to display.
 * @param string $referrer URL to redirect to after displaying the error.
 * @return void
 */
function dmbc_extras_admin_error( $message, $referrer ) {
	if ( ! isset( $_GET['dmbc_error_msg'] ) ) {
		return;
	}

	\add_action(
		'admin_notices',
		function () use ($message) {
			echo '<div class="notice notice-error is-dismissible"><p>' . \esc_html( $message ) . '</p></div>';
		}
	);

	$redirect_url = \remove_query_arg( 'dmbc_error_msg', $referrer );
	\wp_safe_redirect( $redirect_url );
	exit;
}


