<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

/*
function my_plugin_process_admin_form() {
	// 1. Perform your data validation or action here
	$success = false; // Simulate an error occurring

	if ( ! $success ) {
		// 2. Build the redirect URL with an error flag parameter
		$redirect_url = add_query_arg( 'my_plugin_error', 'invalid_input', admin_url( 'admin.php?page=my-plugin-page' ) );

		// 3. Redirect safely and exit immediately
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
add_action( 'admin_post_my_form_action', 'my_plugin_process_admin_form' );
*/

function dmbc_extras_render_song_list_delete_page() {
	$_GET['action'] = 'view';
	$edit_id = isset( $_GET['song_list_id'] ) ? intval( $_GET['song_list_id'] ) : 0;
	echo dmbc_extras_render_song_list_view_page( $edit_id );
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


function dmbc_extras_handle_delete_song_list_form() {
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
function dmbc_extras_send_song_list_to_roles( $song_list_id, $roles = null ) {
	$roles = null === $roles ? dmbc_extras_get_song_list_recipient_roles() : (array) $roles;
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

	$default_recipient = dmbc_extras_get_song_list_default_recipient();
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
function dmbc_extras_send_song_list_to_role( $song_list_id, $role ) {
	return dmbc_extras_send_song_list_to_roles( $song_list_id, array( $role ) );
}

/**
 * Handles the rehearsal song list form submission.
 *
 * Validates the nonce and user capabilities, sanitizes submitted values,
 * saves the song list post, and stores the selected songs in post meta.
 *
 * @return void
 */
function dmbc_extras_handle_song_list_form() {
	if ( isset( $_POST['dmbc_delete_song_list'] ) ) {
		dmbc_extras_handle_delete_song_list_form();
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
		$song_library_dir = dmbc_extras_get_song_library_directory_path();
		$selected_songs = array_map(
			fn( $full_path ) => dmbc_extras_convert_full_path_to_relative( $song_library_dir, $full_path ),
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
	dmbc_extras_send_song_list_to_roles( $post_id );

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
