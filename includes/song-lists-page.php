<?php
namespace dmbc_extras;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function convert_full_path_to_relative( $pathToRemove, $fullPath ) {
	$normalized_path = \wp_normalize_path( $fullPath );
	return \str_replace( \wp_normalize_path( $pathToRemove ) . '/', '', $normalized_path );
}



function dmbc_render_member_song_lists_page() {
	if ( ! \is_user_logged_in() ) {
		return '<p>Please log in to view the rehearsal song lists.</p>';
	}

	if ( isset( $_GET['song_list_id'] ) ) {
		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			dmbc_render_song_lists_admin_page();
		}
		else if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) {
			dmbc_render_song_list_delete_page();
		}
	}
	else {
		ob_start();

		$table = new SongListTable();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Rehearsal Song Lists', 'dmbc-extras' ); ?></h1>
			<form method="post">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
	return ob_get_clean();
}

function dmbc_render_song_list_view_page( $song_list_id = 0, $date = null ) {
	echo render_song_list_view_page( $song_list_id, $date );
}
