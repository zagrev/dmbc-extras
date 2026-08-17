<?php
namespace dmbc_extras;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

global $dmbc_song_lists_table;

function convert_full_path_to_relative( $pathToRemove, $fullPath ): string {
	$normalized_path = \wp_normalize_path( $fullPath );
	return \str_replace( \wp_normalize_path( $pathToRemove ) . '/', '', $normalized_path );
}

function dmbc_render_song_list_view_page( $song_list_id = 0, $date = null ): void {
	echo render_song_list_view_page( $song_list_id, $date );
}

function dmbc_render_song_list_table_page(): void {
	echo render_song_list_table_page();
}
