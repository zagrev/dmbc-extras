<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_get_song_folder_choices() {
	$song_library_dir = WP_CONTENT_DIR . '/dmbc-song-library';

	if ( ! is_dir( $song_library_dir ) ) {
		return array();
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $song_library_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	$choices = array();

	foreach ( $iterator as $path ) {
		if ( ! $path->isDir() ) {
			continue;
		}

		$normalized_path = wp_normalize_path( $path->getPathname() );
		$relative_path   = str_replace( wp_normalize_path( $song_library_dir ) . '/', '', $normalized_path );

		if ( empty( $relative_path ) ) {
			continue;
		}

		$choices[ $normalized_path ] = $relative_path;
	}

	ksort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

	return $choices;
}

function dmbc_extras_render_song_lists_admin_page() {
	$sort_value   = isset( $_GET['dmbc_song_sort'] ) ? sanitize_text_field( wp_unslash( $_GET['dmbc_song_sort'] ) ) : 'modified';
	$edit_id      = isset( $_GET['dmbc_song_list_id'] ) ? absint( wp_unslash( $_GET['dmbc_song_list_id'] ) ) : 0;
	$edit_post    = $edit_id > 0 ? get_post( $edit_id ) : null;
	$edit_title   = '';
	$edit_content = '';
	$edit_songs   = array();

	if ( $edit_post ) {
		$edit_title   = get_the_title( $edit_post );
		$edit_content = get_the_excerpt( $edit_post );
		$edit_songs   = get_post_meta( $edit_post->ID, 'dmbc_song_list_songs', true );
	}

	if ( 'title' === $sort_value ) {
		$orderby = 'title';
		$order   = 'ASC';
	} else {
		$orderby = 'modified';
		$order   = 'DESC';
	}

	$song_lists   = get_posts(
		array(
			'post_type'   => 'dmbc_song_list',
			'post_status' => 'publish',
			'numberposts' => 20,
			'orderby'     => $orderby,
			'order'       => $order,
		)
	);
	$song_folders = dmbc_extras_get_song_folder_choices();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rehearsal Song Lists', 'dmbc-extras' ); ?></h1>

		<form method="post" action="">
			<?php wp_nonce_field( 'dmbc_create_song_list', 'dmbc_song_list_nonce' ); ?>
			<input type="hidden" name="dmbc_song_list_id" value="<?php echo esc_attr( $edit_id ); ?>">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="dmbc_song_list_title"><?php esc_html_e( 'Song List Title', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<input type="text" id="dmbc_song_list_title" name="dmbc_song_list_title" class="regular-text" value="<?php echo esc_attr( $edit_title ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="dmbc_song_list_songs"><?php esc_html_e( 'Select Songs', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<?php if ( empty( $song_folders ) ) : ?>
								<p class="description"><?php esc_html_e( 'Create folders inside wp-content/dmbc-song-library to populate this selector.', 'dmbc-extras' ); ?></p>
							<?php else : ?>
							<div style="display:flex; gap:12px; align-items:flex-start;">
								<div>
									<label for="dmbc_available_song_folders"><?php esc_html_e( 'Available songs', 'dmbc-extras' ); ?></label>
									<select id="dmbc_available_song_folders" multiple size="10" class="large-text" style="min-width: 240px;">
										<?php foreach ( $song_folders as $song_path => $song_label ) : ?>
											<option value="<?php echo esc_attr( $song_path ); ?>"><?php echo esc_html( $song_label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Double-click a folder to add it, or use multi-select and click Add Selected.', 'dmbc-extras' ); ?></p>
								</div>
								<div style="display:flex; flex-direction:column; gap:8px; padding-top:24px;">
									<button type="button" id="dmbc_add_selected_song_folders" class="button button-secondary"><?php esc_html_e( 'Add Selected', 'dmbc-extras' ); ?></button>
									<button type="button" id="dmbc_remove_selected_song_folders" class="button button-secondary"><?php esc_html_e( 'Remove Selected', 'dmbc-extras' ); ?></button>
									<button type="button" id="dmbc_clear_selected_song_folders" class="button button-secondary"><?php esc_html_e( 'Clear All', 'dmbc-extras' ); ?></button>
									<button type="button" id="dmbc_move_up_selected_song_folders" class="button button-secondary"><?php esc_html_e( 'Move Up', 'dmbc-extras' ); ?></button>
									<button type="button" id="dmbc_move_down_selected_song_folders" class="button button-secondary"><?php esc_html_e( 'Move Down', 'dmbc-extras' ); ?></button>
								</div>
								<div>
									<label for="dmbc_selected_song_folders"><?php esc_html_e( 'Selected songs', 'dmbc-extras' ); ?></label>
									<select id="dmbc_selected_song_folders" name="dmbc_song_list_songs[]" multiple size="10" class="large-text" style="min-width: 240px;">
										<?php foreach ( $edit_songs as $selected_song ) : ?>
											<option value="<?php echo esc_attr( $selected_song ); ?>" selected><?php echo esc_html( $selected_song ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'These folder names will be stored with the new song list.', 'dmbc-extras' ); ?></p>
								</div>
							</div>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="dmbc_song_list_content"><?php esc_html_e( 'Songs / Notes', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<textarea id="dmbc_song_list_content" name="dmbc_song_list_content" rows="8" class="large-text"><?php echo esc_textarea( $edit_content ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( $edit_id > 0 ? __( 'Update Song List', 'dmbc-extras' ) : __( 'Create Song List', 'dmbc-extras' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Existing Song Lists', 'dmbc-extras' ); ?></h2>
		<form method="get" action="">
			<input type="hidden" name="page" value="dmbc-rehearsal-song-lists">
			<label for="dmbc_song_sort"><?php esc_html_e( 'Sort by', 'dmbc-extras' ); ?></label>
			<select id="dmbc_song_sort" name="dmbc_song_sort">
				<option value="title" <?php selected( $sort_value, 'title' ); ?>><?php esc_html_e( 'Name', 'dmbc-extras' ); ?></option>
				<option value="modified" <?php selected( $sort_value, 'modified' ); ?>><?php esc_html_e( 'Update Date', 'dmbc-extras' ); ?></option>
			</select>
			<?php submit_button( __( 'Apply', 'dmbc-extras' ), 'secondary', '', false ); ?>
		</form>
		<?php if ( empty( $song_lists ) ) : ?>
			<p><?php esc_html_e( 'No rehearsal song lists yet.', 'dmbc-extras' ); ?></p>
		<?php else : ?>
			<ul>
				<?php foreach ( $song_lists as $song_list ) : ?>
					<?php $stored_songs = get_post_meta( $song_list->ID, 'dmbc_song_list_songs', true ); ?>
					<li>
						<strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=dmbc-rehearsal-song-lists&dmbc_song_list_id=' . (int) $song_list->ID ) ); ?>"><?php echo esc_html( get_the_title( $song_list ) ); ?></a></strong>
						<div><?php echo wp_kses_post( get_the_excerpt( $song_list ) ); ?></div>
						<?php if ( is_array( $stored_songs ) && ! empty( $stored_songs ) ) : ?>
							<div><strong><?php esc_html_e( 'Songs:', 'dmbc-extras' ); ?></strong> <?php echo esc_html( implode( ', ', $stored_songs ) ); ?></div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<script>
	jQuery( document ).ready( function ( $ ) {
		var $available = $( '#dmbc_available_song_folders' );
		var $selected = $( '#dmbc_selected_song_folders' );
		var addSelectedToList = function () {
			$available.find( 'option:selected' ).each( function () {
				var $option = $( this );
				if ( $selected.find( 'option[value="' + $option.val() + '"]' ).length ) {
					return;
				}
				$selected.append( $( '<option></option>' ).val( $option.val() ).text( $option.text() ) );
			} );
		};

		$available.on( 'dblclick', 'option', function () {
			var $option = $( this );
			if ( $selected.find( 'option[value="' + $option.val() + '"]' ).length ) {
				return;
			}
			$selected.append( $( '<option></option>' ).val( $option.val() ).text( $option.text() ) );
		} );

		$selected.on( 'dblclick', 'option', function () {
			$( this ).remove();
		} );

		$( '#dmbc_add_selected_song_folders' ).on( 'click', addSelectedToList );
		$( '#dmbc_remove_selected_song_folders' ).on( 'click', function () {
			$selected.find( 'option:selected' ).remove();
		} );
		$( '#dmbc_clear_selected_song_folders' ).on( 'click', function () {
			$selected.find( 'option' ).remove();
		} );

		$( '#dmbc_move_up_selected_song_folders' ).on( 'click', function () {
			var selected = $selected.find( 'option:selected' );
			selected.each( function () {
				var $option = $( this );
				var prev = $option.prev();
				if ( prev.length ) {
					$option.insertBefore( prev );
				}
			} );
		} );

		$( '#dmbc_move_down_selected_song_folders' ).on( 'click', function () {
			var selected = $selected.find( 'option:selected' );
			$( selected.get().reverse() ).each( function () {
				var $option = $( this );
				var next = $option.next();
				if ( next.length ) {
					$option.insertAfter( next );
				}
			} );
		} );
	} );
	</script>
	<?php
}
