<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function dmbc_extras_add_admin_menu() {
	add_menu_page(
		__( 'Rehearsal Song Lists', 'dmbc-extras' ),
		__( 'Rehearsal Songs', 'dmbc-extras' ),
		'edit_song_list',
		'dmbc-rehearsal-song-lists',
		__NAMESPACE__ . '\dmbc_extras_render_song_lists_admin_page',
		'dashicons-list-view',
		25
	);
}
