<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

function dmbc_extras_handle_delete_song_list_form() {
	if ( ! isset( $_POST['dmbc_song_list_delete_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['dmbc_song_list_delete_nonce'] ) ), 'dmbc_delete_song_list' ) ) {
		return;
	}

	if ( ! \current_user_can( 'edit_song_list' ) && ! \current_user_can( 'manage_options' ) ) {
		return;
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
		} else {
			\add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to delete the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
				}
			);
		}
	} else {
		\add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No rehearsal song list was selected for deletion.', 'dmbc-extras' ) . '</p></div>';
			}
		);
	}
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
	$selected_songs = isset( $_POST['dmbc_song_list_songs'] ) ? (array) $_POST['dmbc_song_list_songs'] : array();

	if ( isset( $_POST['dmbc_song_list_songs'] ) && is_array( $_POST['dmbc_song_list_songs'] ) ) {
		$song_library_dir = dmbc_extras_get_song_library_directory_path();
		$selected_songs = array_map(
			function ( $song ) use ( $song_library_dir ) {
				$normalized_path = \wp_normalize_path( \sanitize_text_field( \wp_unslash( $song ) ) );
				return \str_replace( $song_library_dir, '', $normalized_path );
			},
			$selected_songs
		);

		$selected_songs = array_unique( $selected_songs );
	} else {
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

	\clean_post_cache( $post_id );

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
