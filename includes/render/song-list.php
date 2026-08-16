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
		<h1><?php echo esc_html( $song_list_title ); ?> for <?php echo esc_html( $rehearsal_date ); ?></h1>

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


function dmbc_render_song_list_delete_page(): string {
	$_GET['action'] = 'view';
	$edit_id = isset( $_GET['song_list_id'] ) ? intval( $_GET['song_list_id'] ) : 0;
	$view_page = render_song_list_view_page( $edit_id );

	ob_start();
	?>
	<form method="post" action="" id="dmbc_delete_song_list_form">
		<?php \wp_nonce_field( 'dmbc_delete_song_list', 'dmbc_song_list_delete_nonce' ); ?>
		<input type="hidden" name="dmbc_song_list_id" value="<?php echo esc_attr( $edit_id ); ?>">
		<?php
		\submit_button(
			__( 'Delete song list?', 'dmbc-extras' ),
			'warn large danger btn-danger',
			'dmbc_delete_song_list',
			false,
			'style="background-color:#d63638 !important; border-color:#d63638 !important; color:#fff !important;
		padding:0.75rem1.25rem; font-size:1rem;"'
		); ?>
	</form>
	<?php
	return ob_get_clean();
}


function render_song_list_table_page() {
	global $dmbc_song_lists_table;

	if ( ! \is_user_logged_in() ) {
		return '<p>Please log in to view the rehearsal song lists.</p>';
	}

	if ( isset( $_GET['song_list_id'] ) ) {
		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			return dmbc_render_song_lists_admin_page();
		}
		else if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) {
			return dmbc_render_song_list_delete_page();
		}
	}
	else {
		return render_member_song_lists_table_page();
	}
	return 'unexpected action, not edit/delete but has song_list_id=' . esc_html( $_GET['song_list_id'] );
}

function render_song_list_edit_page( $edit_id = 0 ): string {
	$edit_post = $edit_id > 0 ? \get_post( $edit_id ) : null;
	$edit_title = '';
	$edit_content = '';
	$edit_songs = [];
	$rehearsal_date = ''; // default to the next monday
	$next_monday = strtotime( 'next monday' );
	if ( $next_monday ) {
		$rehearsal_date = date( 'Y-m-d', $next_monday );
	}

	if ( $edit_post ) {
		$edit_title = \get_the_title( $edit_post );
		$edit_content = \get_the_excerpt( $edit_post );
		$edit_songs = \get_post_meta( $edit_post->ID, 'dmbc_song_list_songs' )[0];
		$rehearsal_date = \get_post_meta( $edit_post->ID, 'dmbc_song_list_rehearsal_date', true );
	}

	$song_folders = get_song_folder_choices();

	ob_start();
	?>
	<div class="wrap">
		<?php $action = $edit_id > 0 ? 'Update' : 'Add'; ?>
		<h1><?php esc_html_e( "$action Rehearsal Song List", 'dmbc-extras' ); ?></h1>

		<form method="post" action="">
			<?php \wp_nonce_field( 'dmbc_create_song_list', 'dmbc_song_list_nonce' ); ?>
			<input type="hidden" name="dmbc_song_list_id" value="<?php echo esc_attr( $edit_id ); ?>">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label
								for="dmbc_song_list_title"><?php esc_html_e( 'Song List Title', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<input type="text" id="dmbc_song_list_title" name="dmbc_song_list_title" class="regular-text"
								value="<?php echo esc_attr( $edit_title ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="dmbc_song_list_rehearsal_date">
								<?php \esc_html_e( 'Rehearsal date', 'dmbc-extras' ); ?>
							</label>
						</th>
						<td>
							<input type="date" id="dmbc_song_list_rehearsal_date" name="dmbc_song_list_rehearsal_date"
								value="<?php echo \esc_attr( $rehearsal_date ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="dmbc_song_list_songs"><?php esc_html_e( 'Select Songs', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<?php if ( empty( $song_folders ) ) : ?>
								<p class="description">
									<?php echo esc_html( sprintf( __( 'Create folders inside %s to populate this selector.', 'dmbc-extras' ), get_song_library_directory_path() ) ); ?>
								</p>
							<?php else : ?>
								<div style="display:flex; gap:12px; align-items:flex-start;">
									<div>
										<label
											for="dmbc_available_song_folders"><?php esc_html_e( 'Available songs', 'dmbc-extras' ); ?></label>
										<select id="dmbc_available_song_folders" multiple size="10" class="large-text"
											style="min-width: 240px;">
											<?php foreach ( $song_folders as $song_path => $song_label ) : ?>
												<option value="<?php echo esc_attr( $song_path ); ?>">
													<?php echo esc_html( $song_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Double-click a folder to add it, or use multi-select and click Add Selected.', 'dmbc-extras' ); ?>
										</p>
									</div>
									<div style="display:flex; flex-direction:column; gap:8px; padding-top:24px;">
										<button type="button" id="dmbc_add_selected_song_folders"
											class="button button-secondary"><?php \esc_html_e( 'Add Selected', 'dmbc-extras' ); ?></button>
										<button type="button" id="dmbc_remove_selected_song_folders"
											class="button button-secondary"><?php \esc_html_e( 'Remove Selected', 'dmbc-extras' ); ?></button>
										<button type="button" id="dmbc_clear_selected_song_folders"
											class="button button-secondary"><?php \esc_html_e( 'Clear All', 'dmbc-extras' ); ?></button>
										<button type="button" id="dmbc_move_up_selected_song_folders"
											class="button button-secondary"><?php \esc_html_e( 'Move Up', 'dmbc-extras' ); ?></button>
										<button type="button" id="dmbc_move_down_selected_song_folders"
											class="button button-secondary"><?php \esc_html_e( 'Move Down', 'dmbc-extras' ); ?></button>
									</div>
									<div>
										<label
											for="dmbc_selected_song_folders"><?php \esc_html_e( 'Selected songs', 'dmbc-extras' ); ?></label>
										<select id="dmbc_selected_song_folders" name="dmbc_song_list_songs[]" multiple size="10"
											class="large-text" style="min-width: 240px;">
											<?php foreach ( $edit_songs as $song_path => $song_label ) : ?>
												<option value="<?php echo esc_attr( $song_path ); ?>">
													<?php echo esc_html( $song_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php \esc_html_e( 'These folder names will be stored with the new song list.', 'dmbc-extras' ); ?>
										</p>
									</div>
								</div>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label
								for="dmbc_song_list_content"><?php \esc_html_e( 'Songs / Notes', 'dmbc-extras' ); ?></label>
						</th>
						<td>
							<textarea id="dmbc_song_list_content" name="dmbc_song_list_content" rows="8"
								class="large-text"><?php echo \esc_textarea( $edit_content ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<?php \submit_button( $edit_id > 0 ? __( 'Update Song List', 'dmbc-extras' ) : __( 'Create Song List', 'dmbc-extras' ) ); ?>
		</form>
		<div style="display:flex; justify-content:flex-end; margin-top:16px;">
			<form method="post" action="" id="dmbc_delete_song_list_form">
				<?php \wp_nonce_field( 'dmbc_delete_song_list', 'dmbc_song_list_delete_nonce' ); ?>
				<input type="hidden" name="dmbc_song_list_id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php $delete_button_attributes = $edit_id > 0 ? 'style="background-color:#d63638 !important; border-color:#d63638 !important; color:#fff !important; padding:0.75rem 1.25rem; font-size:1rem;"' : 'disabled style="padding:0.75rem 1.25rem; font-size:1rem;"';
				\submit_button(
					__( 'Delete Song List', 'dmbc-extras' ),
					'warn large danger btn-danger',
					'dmbc_delete_song_list',
					false,
					$delete_button_attributes
				); ?>
			</form>
		</div>

	</div>
	<script>
		var dmbcDeleteConfirmation = <?php echo wp_json_encode( __( 'Are you sure you want to delete this song list?', 'dmbc-extras' ) ); ?>;
		jQuery(document).ready(function ($) {
			$('#dmbc_delete_song_list_form').on('submit', function (event) {
				if (!window.confirm(dmbcDeleteConfirmation)) {
					event.preventDefault();
				}
			});

			var $available = $('#dmbc_available_song_folders');
			var $selected = $('#dmbc_selected_song_folders');

			var addSelectedToList = function () {
				$available.find('option:selected').each(function () {
					var $option = $(this);
					if ($selected.find('option[value="' + $option.val() + '"]').length) {
						return;
					}
					$selected.append($('<option></option>').val($option.val()).text($option.text()));
				});
			};

			$available.on('dblclick', 'option', function () {
				var $option = $(this);
				if ($selected.find('option[value="' + $option.val() + '"]').length) {
					return;
				}
				$selected.append($('<option></option>').val($option.val()).text($option.text()));
			});

			$available.on('keydown', function (event) {
				if ('Enter' === event.key || 13 === event.which) {
					event.preventDefault();
					addSelectedToList();
				}
			});

			$selected.on('dblclick', 'option', function () {
				$(this).remove();
			});

			$('#dmbc_add_selected_song_folders').on('click', addSelectedToList);

			$('#dmbc_remove_selected_song_folders').on('click', function () {
				$selected.find('option:selected').remove();
			});

			$('#dmbc_clear_selected_song_folders').on('click', function () {
				$selected.find('option').remove();
			});

			$('#dmbc_move_up_selected_song_folders').on('click', function () {
				var selected = $selected.find('option:selected');
				selected.each(function () {
					var $option = $(this);
					var prev = $option.prev();
					if (prev.length) {
						$option.insertBefore(prev);
					}
				});
			});

			$('#dmbc_move_down_selected_song_folders').on('click', function () {
				var selected = $selected.find('option:selected');
				$(selected.get().reverse()).each(function () {
					var $option = $(this);
					var next = $option.next();
					if (next.length) {
						$option.insertAfter(next);
					}
				});
			});

			$('form').on('submit', function () {
				$selected.find('option').prop('selected', true);
			});
		});
	</script>
	<?php
	return ob_get_clean();
}


function render_member_song_lists_table_page(): string|bool {
	global $dmbc_song_lists_table;

	$dmbc_song_lists_table->prepare_items();
	ob_start();
	?>
	<div class="wrap">
		<h1>
			<?php esc_html_e( 'Rehearsal Song Lists', 'dmbc-extras' ); ?>
		</h1>
		<form method="post">
			<?php $dmbc_song_lists_table->display(); ?>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
