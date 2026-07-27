<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_add_custom_capabilities() {
	$roles = array( 'administrator', 'editor' );

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->add_cap( 'edit_song_list' );
		}
	}
}

function dmbc_extras_activate() {
	// Check if the Song List CPT is registered
	if ( ! post_type_exists( 'song_list' ) ) {
		// Register the Song List CPT
		dmbc_extras_register_song_list_post_type();
	}

	dmbc_extras_add_custom_capabilities();

	// Flush rewrite rules to ensure the new CPT is recognized
	flush_rewrite_rules();

	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'dmbc-extras is active and ready to add custom capabilities for the Dayton Metro Barbershop Chorus.', 'dmbc-extras' ) . '</p></div>';
		}
	);
}
