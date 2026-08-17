<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function create_song_list_table() {
	global $dmbc_song_lists_table;
	if ( ! isset( $dmbc_song_lists_table ) ) {
		$dmbc_song_lists_table = new SongListTable();
	}
}
/**
 * Renders the rehearsal song lists admin page.
 *
 * @return void
 */
function dmbc_render_song_lists_admin_page() {
	$edit_id = isset( $_GET['song_list_id'] ) ? \absint( \wp_unslash( $_GET['song_list_id'] ) ) : 0;
	echo render_song_list_edit_page( $edit_id );
}
function get_song_folder_choices() {
	$song_library_dir = get_song_library_directory_path();

	if ( ! is_dir( $song_library_dir ) ) {
		return array();
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $song_library_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	$choices = array();

	$exclusion_regexes = get_song_library_exclusion_regexes();
	foreach ( $iterator as $path ) {

		$full_path = \wp_normalize_path( $path->getpathname() );
		if ( $path->isDir() and ! \str_contains( $full_path, 'Archived Music' ) ) {
			$exclude = false;

			foreach ( $exclusion_regexes as $regex ) {
				if ( preg_match( '/' . $regex . '/', $full_path ) ) {
					$exclude = true;
					break;
				}
			}

			if ( ! $exclude ) {

				$relative_path = convert_full_path_to_relative( $song_library_dir, $full_path );

				if ( ! empty( $relative_path ) ) {
					$choices[ $full_path ] = $relative_path;
				}
			}
		}
	}

	ksort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

	return $choices;
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
