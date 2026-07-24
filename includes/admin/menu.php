<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_add_admin_menu() {
	add_menu_page(
		__( 'Rehearsal Song Lists', 'dmbc-extras' ),
		__( 'Rehearsal Songs', 'dmbc-extras' ),
		'manage_options',
		'dmbc-rehearsal-song-lists',
		'dmbc_extras_render_song_lists_admin_page',
		'dashicons-list-view',
		25
	);
}
