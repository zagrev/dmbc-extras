<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_handle_song_list_form() {
	if ( ! isset( $_POST['dmbc_song_list_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dmbc_song_list_nonce'] ) ), 'dmbc_create_song_list' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$title          = isset( $_POST['dmbc_song_list_title'] ) ? sanitize_text_field( wp_unslash( $_POST['dmbc_song_list_title'] ) ) : '';
	$content        = isset( $_POST['dmbc_song_list_content'] ) ? wp_kses_post( wp_unslash( $_POST['dmbc_song_list_content'] ) ) : '';
	$song_list_id   = isset( $_POST['dmbc_song_list_id'] ) ? absint( wp_unslash( $_POST['dmbc_song_list_id'] ) ) : 0;
	$selected_songs = array();

	if ( isset( $_POST['dmbc_song_list_songs'] ) && is_array( $_POST['dmbc_song_list_songs'] ) ) {
		$selected_songs = array_map(
			static function ( $song ) {
				return basename( wp_normalize_path( sanitize_text_field( wp_unslash( $song ) ) ) );
			},
			$_POST['dmbc_song_list_songs']
		);

		$selected_songs = array_values( array_unique( array_filter( $selected_songs ) ) );

		if ( ! empty( $selected_songs ) ) {
			$selected_song_lines = array_map(
				static function ( $song ) {
					return '- ' . $song;
				},
				$selected_songs
			);
			$selected_song_text  = implode( "\n", $selected_song_lines );

			if ( empty( $content ) ) {
				$content = $selected_song_text;
			} else {
				$content = $selected_song_text . "\n\n" . $content;
			}
		}
	}

	if ( empty( $title ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter a title for the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
			}
		);
		return;
	}

	$post_data = array(
		'post_type'    => 'dmbc_song_list',
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
	);

	if ( $song_list_id > 0 ) {
		$post_data['ID'] = $song_list_id;
	}

	if ( ! empty( $selected_songs ) ) {
		$post_data['meta_input'] = array(
			'dmbc_song_list_songs' => $selected_songs,
		);
	} elseif ( $song_list_id > 0 ) {
		$post_data['meta_input'] = array(
			'dmbc_song_list_songs' => array(),
		);
	}

	$post_id = $song_list_id > 0 ? wp_update_post( $post_data, true ) : wp_insert_post( $post_data, true );

	if ( is_wp_error( $post_id ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to create the rehearsal song list.', 'dmbc-extras' ) . '</p></div>';
			}
		);
		return;
	}

	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rehearsal song list created successfully.', 'dmbc-extras' ) . '</p></div>';
		}
	);
}
