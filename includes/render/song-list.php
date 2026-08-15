<?php
namespace dmbc_extras;
if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function render_song_list_view_page( $song_list_id = 0, $date = null ) {
	if ( ! \is_user_logged_in() ) {
		return '<p>Please log in to view this song list.</p>';
	}

	// if no id and no date provided, find the rehearsal song list that has the lowest date and is greater than or equal to today
	$song_list = $song_list_id ? \get_post( $song_list_id ) : null;
	if ( ! $song_list && ! $date ) {
		$upcoming_song_lists = \get_posts(
			array(
				'post_type' => 'dmbc_song_list',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'orderby' => 'meta_value',
				'order' => 'ASC',
				'meta_key' => 'dmbc_song_list_rehearsal_date',
				'meta_value' => \current_time( 'Y-m-d' ),
				'meta_compare' => '>=',
			)
		);
		$song_list = ! empty( $upcoming_song_lists ) ? $upcoming_song_lists[0] : null;
	}

	if ( ( ! $song_list || 'dmbc_song_list' !== $song_list->post_type ) && ! $date ) {
		return '<p>Rehearsal Song List not found.</p>';
	}

	$song_list_title = \get_the_title( $song_list );
	$rehearsal_date = \get_post_meta( $song_list->ID, 'dmbc_song_list_rehearsal_date', true );
	if ( empty( $rehearsal_date ) ) {
		$rehearsal_date = date( 'Y-m-d', strtotime( $date ) );
	}
	$songs = \get_post_meta( $song_list->ID, 'dmbc_song_list_songs', true );
	if ( ! is_array( $songs ) ) {
		$songs = array();
	}

	ob_start();
	?>
	<div class="dmbc-song-list-view">
		<h1><?php echo esc_html( $song_list_title );
		echo " for "; ?><?php echo esc_html( $rehearsal_date ); ?></h1>

		<?php if ( ! empty( $songs ) ) : ?>
			<h2><?php esc_html_e( 'Songs', 'dmbc-extras' );
			$song_library_dir = get_song_library_directory_path();
			$song_library_path = convert_full_path_to_relative( WP_CONTENT_DIR, $song_library_dir );
			?></h2>
			<ul>
				<?php foreach ( $songs as $song ) :
					$song_path = is_array( $song ) ? implode( ',', $song ) : (string) $song;
					$song_url_path = convert_full_path_to_relative( WP_CONTENT_DIR, $song_path );
					$song_url = \content_url( "$song_library_path/$song_url_path" );
					?>
					<li><a href="<?php echo \esc_url( $song_url ); ?>"><?php echo esc_html( $song_path ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'No songs selected for this list.', 'dmbc-extras' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
