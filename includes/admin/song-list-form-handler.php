<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

/**
 * Renders the rehearsal song lists admin page.
 *
 * @return void
 */
function dmbc_render_song_lists_admin_page() {
	$edit_id = isset( $_GET['song_list_id'] ) ? \absint( \wp_unslash( $_GET['song_list_id'] ) ) : 0;
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
}


function dmbc_render_song_list_delete_page() {
	$_GET['action'] = 'view';
	$edit_id = isset( $_GET['song_list_id'] ) ? intval( $_GET['song_list_id'] ) : 0;
	$view_page = render_song_list_view_page( $edit_id );
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
}


function handle_delete_song_list_form() {
	if ( ! isset( $_POST['dmbc_song_list_delete_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['dmbc_song_list_delete_nonce'] ) ), 'dmbc_delete_song_list' ) ) {
		return;
	}


	if ( ! \current_user_can( 'edit_song_list' ) && ! \current_user_can( 'manage_options' ) ) {
		die( 'You do not have permission to delete this song list.' );
	}

	$song_list_id = isset( $_POST['dmbc_song_list_id'] ) ? \absint( \wp_unslash( $_POST['dmbc_song_list_id'] ) ) : 0;
	if ( $song_list_id > 0 ) {
		$deleted_post = \wp_delete_post( $song_list_id, true );
		if ( $deleted_post ) {
			\add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rehearsal song list deleted successfully.', 'dmbc-extras' ) . '</p></div>';
				}
			);
			\add_action( 'admin_init', function () {
				\wp_safe_redirect( \admin_url( "admin.php?page=dmbc-song-lists" ) );
				exit;
			} );
		}
		else {
			\add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to delete the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
				}
			);
		}
	}
	else {
		\add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No rehearsal song list was selected for deletion.', 'dmbc-extras' ) . '</p></div>';
			}
		);
	}
}

/**
 * Sends a rehearsal song list to users who belong to configured roles.
 *
 * @param int        $song_list_id The rehearsal song list post ID.
 * @param array|null $roles        Optional role slugs to notify.
 * @return bool Whether WordPress accepted the email for delivery.
 */
function send_song_list_to_roles( $song_list_id, $roles = null ) {
	$roles = null === $roles ? get_song_list_recipient_roles() : (array) $roles;
	$recipients = array();
	if ( ! empty( $roles ) ) {
		$users = \get_users(
			array(
				'role__in' => $roles,
			)
		);
		$recipients = array_map(
			function ( $user ) {
				return isset( $user->user_email ) ? $user->user_email : '';
			},
			(array) $users
		);
	}

	$default_recipient = get_song_list_default_recipient();
	if ( ! empty( $default_recipient ) ) {
		$recipients[] = $default_recipient;
	}
	$recipients = array_values( array_unique( array_filter( $recipients ) ) );
	$recipients = array_values(
		array_filter(
			$recipients,
			function ( $recipient ) {
				return function_exists( 'is_email' ) ? \is_email( $recipient ) : filter_var( $recipient, FILTER_VALIDATE_EMAIL );
			}
		)
	);

	if ( empty( $recipients ) ) {
		return false;
	}

	$song_list = \get_post( $song_list_id );
	if ( ! $song_list || 'dmbc_song_list' !== $song_list->post_type ) {
		return false;
	}

	$songs = \get_post_meta( $song_list_id, 'dmbc_song_list_songs', true );
	$songs = is_array( $songs ) ? $songs : array();
	$rehearsal_date = \get_post_meta( $song_list_id, 'dmbc_song_list_rehearsal_date', true );
	$message = "Rehearsal song list: {$song_list->post_title}\n\n";
	if ( ! empty( $rehearsal_date ) ) {
		$message .= "Rehearsal date: {$rehearsal_date}\n\n";
	}
	$message .= $song_list->post_content . "\n\nSongs:\n";
	$message .= empty( $songs ) ? "No songs selected.\n" : implode( "\n", $songs ) . "\n";

	return \wp_mail(
		$recipients,
		'Rehearsal song list: ' . $song_list->post_title,
		$message
	);
}

/**
 * Sends a rehearsal song list to users who belong to one role.
 *
 * @param int    $song_list_id The rehearsal song list post ID.
 * @param string $role         The role slug whose members should receive the list.
 * @return bool Whether WordPress accepted the email for delivery.
 */
function send_song_list_to_role( $song_list_id, $role ) {
	return send_song_list_to_roles( $song_list_id, array( $role ) );
}

/**
 * Handles the rehearsal song list form submission.
 *
 * Validates the nonce and user capabilities, sanitizes submitted values,
 * saves the song list post, and stores the selected songs in post meta.
 *
 * @return void
 */
function handle_song_list_form() {
	if ( isset( $_POST['dmbc_delete_song_list'] ) ) {
		handle_delete_song_list_form();
	}

	if ( ! isset( $_POST['dmbc_song_list_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['dmbc_song_list_nonce'] ) ), 'dmbc_create_song_list' ) ) {
		return;
	}

	if ( ! \current_user_can( 'edit_song_list' ) && ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$title = isset( $_POST['dmbc_song_list_title'] ) ? \sanitize_text_field( \wp_unslash( $_POST['dmbc_song_list_title'] ) ) : '';
	$content = isset( $_POST['dmbc_song_list_content'] ) ? \wp_kses_post( \wp_unslash( $_POST['dmbc_song_list_content'] ) ) : '';
	$song_list_id = isset( $_POST['dmbc_song_list_id'] ) ? \absint( \wp_unslash( $_POST['dmbc_song_list_id'] ) ) : 0;
	$rehearsal_date = isset( $_POST['dmbc_song_list_rehearsal_date'] ) ? \sanitize_text_field( \wp_unslash( $_POST['dmbc_song_list_rehearsal_date'] ) ) : '';
	$selected_songs = isset( $_POST['dmbc_song_list_songs'] ) ? (array) $_POST['dmbc_song_list_songs'] : array();
	if ( ! empty( $rehearsal_date ) ) {
		$date = \DateTime::createFromFormat( '!Y-m-d', $rehearsal_date );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $rehearsal_date ) {
			$rehearsal_date = '';
		}
	}

	if ( isset( $_POST['dmbc_song_list_songs'] ) && is_array( $_POST['dmbc_song_list_songs'] ) ) {
		$song_library_dir = get_song_library_directory_path();
		$selected_songs = array_map(
			fn( $full_path ) => convert_full_path_to_relative( $song_library_dir, $full_path ),
			$selected_songs
		);

		$selected_songs = array_unique( $selected_songs );
	}
	else {
		\add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please select songs for the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
			}
		);
		return;
	}

	if ( empty( $title ) ) {
		\add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter a title for the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
			}
		);
		return;
	}

	$post_data = array(
		'ID' => $song_list_id,
		'post_type' => 'dmbc_song_list',
		'post_title' => $title,
		'post_content' => $content,
		'post_status' => 'publish',
	);

	$post_id = $song_list_id > 0 ? \wp_update_post( $post_data, true ) : \wp_insert_post( $post_data, true );

	if ( \is_wp_error( $post_id ) ) {
		\add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . \esc_html__( 'Unable to save the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
			}
		);
		return;
	}

	/* Ensure selected songs are stored explicitly in post meta on updates and creates. */
	\update_post_meta( $post_id, 'dmbc_song_list_songs', $selected_songs );
	\update_post_meta( $post_id, 'dmbc_song_list_rehearsal_date', $rehearsal_date );

	\clean_post_cache( $post_id );
	send_song_list_to_roles( $post_id );

	$action = 'created';
	if ( $song_list_id > 0 ) {
		$action = 'updated';
	}
	\add_action(
		'admin_notices',
		function () use ($action) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rehearsal song list ' . $action . ' successfully.', 'dmbc-extras' ) . '</p></div>';
		}
	);
}
