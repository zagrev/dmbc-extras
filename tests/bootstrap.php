<?php
namespace PHPUnit\Framework {
	if ( ! class_exists( 'PHPUnit\Framework\TestCase' ) ) {
		class TestCase {
			public function assertStringContainsString( $needle, $haystack ) {
				if ( strpos( $haystack, $needle ) === false ) {
					throw new \Exception( 'Expected to find "' . $needle . '" in the output.' );
				}
			}

			public function assertArrayHasKey( $key, $array ) {
				if ( ! array_key_exists( $key, $array ) ) {
					throw new \Exception( 'Expected key "' . $key . '" to exist.' );
				}
			}

			public function assertSame( $expected, $actual ) {
				if ( $expected !== $actual ) {
					throw new \Exception( 'Expected values to match.' );
				}
			}
		}
	}
}

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/dmbc-extras-wp-content' );
	}

	if ( ! is_dir( WP_CONTENT_DIR ) ) {
		mkdir( WP_CONTENT_DIR, 0777, true );
	}

	if ( ! is_dir( WP_CONTENT_DIR . '/dmbc-song-library' ) ) {
		mkdir( WP_CONTENT_DIR . '/dmbc-song-library', 0777, true );
		mkdir( WP_CONTENT_DIR . '/dmbc-song-library/Song A', 0777, true );
		mkdir( WP_CONTENT_DIR . '/dmbc-song-library/Song A/Sub Song', 0777, true );
	}

	function __() {
		$args = func_get_args();
		return $args[0] ?? '';
	}

	function esc_html__() {
		$args = func_get_args();
		return $args[0] ?? '';
	}

	function esc_html( $text ) {
		return $text;
	}

	function esc_attr( $text ) {
		return $text;
	}

	function esc_url( $url ) {
		return $url;
	}

	function esc_textarea( $text ) {
		return $text;
	}

	function admin_url( $path ) {
		return $path;
	}

	function wp_nonce_field( $action, $name ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $action ) . '">';
	}

	function submit_button( $text = '' ) {
		echo '<input type="submit" value="' . esc_attr( $text ) . '">';
	}

	function selected( $selected, $current ) {
		echo (string) $selected === (string) $current ? ' selected="selected"' : '';
	}

	function add_action() {}

	function current_user_can() {
		return true;
	}

	function wp_verify_nonce() {
		return true;
	}

	function wp_unslash( $value ) {
		return $value;
	}

	function sanitize_text_field( $value ) {
		return trim( strip_tags( $value ) );
	}

	function wp_kses_post( $value ) {
		return $value;
	}

	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}

	function get_posts( $args = array() ) {
		return $GLOBALS['__dmbc_test_posts'] ?? array();
	}

	function get_post( $post_id ) {
		return $GLOBALS['__dmbc_test_post_lookup'][ $post_id ] ?? null;
	}

	function get_post_meta( $post_id, $meta_key, $single = false ) {
		return $GLOBALS['__dmbc_test_post_meta'][ $post_id ][ $meta_key ] ?? array();
	}

	function get_edit_post_link( $post_id ) {
		return 'edit-post-' . (int) $post_id;
	}

	function get_the_title( $post ) {
		return $post->post_title ?? '';
	}

	function get_the_excerpt( $post ) {
		return $post->post_content ?? '';
	}

	function wp_insert_post( $post_data, $wp_error = false ) {
		$GLOBALS['__dmbc_test_inserted_post'] = $post_data;
		return 123;
	}

	function wp_update_post( $post_data, $wp_error = false ) {
		$GLOBALS['__dmbc_test_updated_post'] = $post_data;
		return $post_data['ID'] ?? 0;
	}

	require_once dirname( __DIR__ ) . '/dmbc-extras.php';
}
