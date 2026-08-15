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
		'view_song_lists',
		'dmbc-rehearsal-song-lists',
		__NAMESPACE__ . '\dmbc_extras_render_member_song_lists_page',
		'dashicons-list-view',
		25
	);

	add_submenu_page(
		'dmbc-rehearsal-song-lists',
		__( 'All Rehearsal Songs', 'dmbc-extras' ),
		__( 'All', 'dmbc-extras' ),
		'view_song_lists',
		'dmbc-rehearsal-song-lists',
		__NAMESPACE__ . '\dmbc_extras_render_member_song_lists_page'
	);

	add_submenu_page(
		'dmbc-rehearsal-song-lists',
		__( 'Add Song List', 'dmbc-extras' ),
		__( 'Add Song List', 'dmbc-extras' ),
		'view_song_lists',
		'dmbc-rehearsal-song-list-add',
		__NAMESPACE__ . '\dmbc_extras_render_song_lists_admin_page'
	);

	add_submenu_page(
		'options-general.php',
		__( 'DMBC Extras', 'dmbc-extras' ),
		__( 'DMBC Extras', 'dmbc-extras' ),
		'manage_options',
		'dmbc-extras-settings',
		__NAMESPACE__ . '\dmbc_extras_render_settings_page'
	);
}
