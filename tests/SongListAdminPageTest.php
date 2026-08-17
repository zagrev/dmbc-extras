<?php
namespace dmbc_extras\Tests;

use function dmbc_extras\create_song_list_table;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;
use function PHPUnit\Framework\assertTrue;

class SongListAdminPageTest extends DmbcTestCase {
	/**
	 * Function test_it_lists_available_song_folders_from_wp_content_directory.
	 */
	public function test_it_lists_available_song_folders_from_wp_content_directory() {
		$post = \WP_Post::create(
			array(
				'ID' => 1,
				'post_title' => 'Test List',
				'post_content' => 'Content',
			)
		);

		expect( 'absint' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'current_time' )->zeroOrMoreTimes()->andReturn( '2026-08-12 12:00:00' );
		expect( 'sanitize_text_field' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
			return str_replace( '\\', '/', $path );
		} );
		expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( fn( $value ) => str_replace( '\\', '/', $value ) );
		expect( 'wp_json_encode' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return json_encode( $value );
		} );
		expect( 'get_post' )->zeroOrMoreTimes()->andReturn( $post );
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
			return 'dmbc_song_list_rehearsal_date' === $key ? '2026-09-15' : [ 'Song A', 'Song B' ];
		} );
		expect( 'get_posts' )->zeroOrMoreTimes()->andReturn( [ $post ] );
		expect( 'get_the_title' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_object ) {
			return $post_object->post_title ?? 'Test List';
		} );
		expect( 'get_the_excerpt' )->zeroOrMoreTimes()->andReturn( 'Content' );
		expect( 'get_the_date' )->zeroOrMoreTimes()->andReturn( '2026-08-10 10:00:00' );
		expect( 'get_the_modified_date' )->zeroOrMoreTimes()->andReturn( '2026-08-10 11:00:00' );
		expect( 'esc_html_e' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			echo $text;
			return true;
		} );
		expect( '__' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );
		expect( 'wp_nonce_field' )->zeroOrMoreTimes()->andReturn( true );
		expect( 'submit_button' )->zeroOrMoreTimes()->andReturnUsing( function ( $text = '' ) {
			echo $text;
			return true;
		} );
		expect( 'selected' )->zeroOrMoreTimes()->andReturn( true );
		expect( 'admin_url' )->zeroOrMoreTimes()->andReturn( 'https://example.test/wp-admin/admin.php' );
		expect( 'esc_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $url ) {
			return str_replace( ' ', '%20', $url );
		} );
		expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_html' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_textarea' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'get_option' )->zeroOrMoreTimes()->andReturn( 'dmbc-song-library' );

		self::create_test_song_list_directory();
		$_GET['dmbc_song_list_id'] = 1;

		ob_start();
		\dmbc_extras\dmbc_render_song_lists_admin_page();
		$output = ob_get_clean();

		// verify that this page structure is correct
		assertTrue( true );
		$this->assertStringContainsString( 'Rehearsal Song List', $output );
		$this->assertStringContainsString( 'Select Songs', $output );
		$this->assertStringContainsString( 'Add Selected', $output );
		$this->assertStringContainsString( 'Remove Selected', $output );
		$this->assertStringContainsString( 'Clear All', $output );
		$this->assertStringContainsString( 'Move Up', $output );
		$this->assertStringContainsString( 'Move Down', $output );
		$this->assertStringContainsString( 'dmbc_selected_song_folders', $output );
		$this->assertStringContainsString( '$available.on(\'keydown\'', $output );
		$this->assertStringContainsString( 'addSelectedToList();', $output );
		$this->assertStringContainsString( 'Song A', $output );
		// $this->assertStringContainsString( 'Update Song List', $output );
		$this->assertStringContainsString( 'dmbc_song_list_rehearsal_date', $output );
		$this->assertStringContainsString( 'type="date"', $output );
		$this->assertStringContainsString( 'Rehearsal date', $output );
		$this->assertStringNotContainsString( 'Existing Song Lists', $output );
		$this->assertStringNotContainsString( 'Sort by', $output );
	}

	public function test_it_renders_member_song_lists_in_descending_rehearsal_date_order() {
		global $dmbc_song_lists_table;

		$older = (object) array(
			'ID' => 2,
			'post_title' => 'Older List',
			'post_content' => 'Old content',
			'post_type' => 'dmbc_song_list',
		);
		$newer = (object) array(
			'ID' => 3,
			'post_title' => 'Newer List',
			'post_content' => 'New content',
			'post_type' => 'dmbc_song_list',
		);

		expect( 'is_user_logged_in' )->zeroOrMoreTimes()->andReturn( true );
		when( 'is_admin' )->justReturn( false );
		expect( 'current_user_can' )->with( 'edit_song_list' )->andReturn( true );
		expect( 'get_posts' )->zeroOrMoreTimes()->andReturn( [ $newer, $older ] );
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
			if ( 3 === $post_id ) {
				return '2026-09-15';
			}
			return '2026-09-01';
		} );
		expect( 'get_the_title' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
			return $post->post_title ?? '';
		} );
		expect( 'get_the_excerpt' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
			return $post->post_content ?? '';
		} );
		expect( 'esc_html__' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_html_e' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			echo $text;
			return true;
		} );
		expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'wp_parse_args' )->zeroOrMoreTimes()->andReturnUsing( function ( $args, $defaults = array () ) {
			return is_array( $args ) ? array_merge( $defaults, $args ) : $defaults;
		} );
		expect( 'convert_to_screen' )->zeroOrMoreTimes()->andReturnUsing( function ( $screen ) {
			return new class ($screen) {
				public $id;
				public $base;

				public function __construct( $screen ) {
					$this->id = (string) $screen;
					$this->base = (string) $screen;
				}

				public function render_screen_reader_content( $content ) {
					return '';
				}
			};
		} );
		expect( 'sanitize_key' )->zeroOrMoreTimes()->andReturnFirstArg();
		when( 'add_filter' )->justReturn( true );
		expect( '__' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( '_x' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( '_n' )->zeroOrMoreTimes()->andReturnUsing( function ( $single, $plural, $number ) {
			return sprintf( 1 === (int) $number ? $single : $plural, $number );
		} );
		expect( 'wp_nonce_field' )->zeroOrMoreTimes()->andReturn( '' );
		expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'number_format_i18n' )->zeroOrMoreTimes()->andReturnUsing( function ( $number ) {
			return (string) $number;
		} );
		expect( 'wp_removable_query_args' )->zeroOrMoreTimes()->andReturn( array() );
		expect( 'set_url_scheme' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'remove_query_arg' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_url' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'get_user_setting' )->zeroOrMoreTimes()->andReturn( array() );
		expect( 'get_column_headers' )->zeroOrMoreTimes()->andReturn( array(
			'cb' => '<input type="checkbox" />',
			'rehearsal_date' => 'Rehearsal Date',
			'name' => 'Name',
			'songs' => 'Songs',
		) );
		expect( 'get_hidden_columns' )->zeroOrMoreTimes()->andReturn( array() );
		expect( 'add_query_arg' )->zeroOrMoreTimes()->andReturnUsing( function ( $args, $url = '' ) {
			return $url ?: 'http://example.com/';
		} );
		expect( 'get_permalink' )->zeroOrMoreTimes()->andReturn( 'http://example.com/song-list/' );
		expect( 'wp_strip_all_tags' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return strip_tags( $text );
		} );

		create_song_list_table();
		$output = \dmbc_extras\render_song_list_table_page();

		print_r( $output );
		$this->assertStringContainsString( 'Rehearsal Song Lists', $output );
		$this->assertStringContainsString( 'Newer List', $output );
		$this->assertStringContainsString( '2026-09-15', $output );
		$this->assertLessThan( strpos( $output, 'Older List' ), strpos( $output, 'Newer List' ) );
	}

	public function test_it_renders_a_single_song_list_view() {
		$song_list = (object) array(
			'ID' => 42,
			'post_type' => 'dmbc_song_list',
			'post_title' => 'Sample Song List',
		);

		expect( 'is_user_logged_in' )->zeroOrMoreTimes()->andReturn( true );
		expect( 'get_post' )->with( 42 )->andReturn( $song_list );
		expect( 'get_the_title' )->zeroOrMoreTimes()->andReturn( 'Sample Song List' );
		expect( 'get_option' )->with( 'song_library_directory', 'dmbc-song-library' )->andReturn( 'dmbc-song-library' );
		expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
			return str_replace( '\\', '/', $path );
		} );
		expect( 'content_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
			return 'https://example.com/wp-content/' . $path;
		} );
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
			if ( 'dmbc_song_list_rehearsal_date' === $key ) {
				return '2026-09-15';
			}
			if ( 'dmbc_song_list_songs' === $key ) {
				return array( 'Song A', 'Song B' );
			}
			return array();
		} );
		expect( 'esc_html_e' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			echo $text;
			return true;
		} );
		expect( 'esc_html' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $url ) {
			return str_replace( ' ', '%20', $url );
		} );

		$output = \dmbc_extras\render_song_list_view_page( 42 );

		$this->assertStringContainsString( 'Sample Song List', $output );
		$this->assertStringContainsString( '2026-09-15', $output );
		$this->assertStringContainsString( 'Song A', $output );
		$this->assertStringContainsString( 'Song B', $output );
		$this->assertStringContainsString( 'href="https://example.com/wp-content/plugins/dmbc-extras//test-song-lists/Song%20A"', $output );
		$this->assertStringContainsString( 'href="https://example.com/wp-content/plugins/dmbc-extras//test-song-lists/Song%20B"', $output );
	}

	public function test_it_uses_the_next_rehearsal_song_list_when_no_id_or_date_is_given() {
		$song_list = (object) array(
			'ID' => 43,
			'post_type' => 'dmbc_song_list',
		);
		$query = array();

		expect( 'is_user_logged_in' )->zeroOrMoreTimes()->andReturn( true );
		expect( 'current_time' )->with( 'Y-m-d' )->andReturn( '2026-08-13' );
		expect( 'get_posts' )->zeroOrMoreTimes()->andReturnUsing( function ( $args ) use ( &$query, $song_list ) {
			$query = $args;
			return array( $song_list );
		} );
		expect( 'get_the_title' )->with( $song_list )->andReturn( 'Next Rehearsal' );
		expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
			return str_replace( '\\', '/', $path );
		} );
		expect( 'content_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
			return 'https://example.com/wp-content/' . $path;
		} );
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
			return 'dmbc_song_list_rehearsal_date' === $key ? '2026-08-20' : array( 'Song A' );
		} );
		expect( 'esc_html_e' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );
		expect( 'esc_html' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_url' )->zeroOrMoreTimes()->andReturnFirstArg();

		unset( $_GET['song_list_id'], $_GET['date'] );
		$output = \dmbc_extras\render_song_list_view_page();

		$this->assertSame( 'dmbc_song_list', $query['post_type'] );
		$this->assertSame( 1, $query['posts_per_page'] );
		$this->assertSame( 'meta_value', $query['orderby'] );
		$this->assertSame( 'ASC', $query['order'] );
		$this->assertSame( 'dmbc_song_list_rehearsal_date', $query['meta_key'] );
		$this->assertSame( '2026-08-13', $query['meta_value'] );
		$this->assertSame( '>=', $query['meta_compare'] );
		$this->assertStringContainsString( 'Next Rehearsal', $output );
	}

	public function test_it_returns_the_configured_song_library_directory() {
		$this->assertSame( $this->song_list_directory, \dmbc_extras\get_song_library_directory_option() );
	}

	public function test_it_lists_subdirectories_for_the_wp_content_browser() {
		$directory = sys_get_temp_dir() . '/dmbc-extras-browser-' . uniqid( '', true );
		$nested_directory = $directory . '/nested';
		mkdir( $nested_directory, 0777, true );

		when( 'wp_normalize_path' )->returnArg();

		$choices = \dmbc_extras\get_wp_content_folder_choices( $directory );
		$normalized_directory = str_replace( '\\', '/', $nested_directory );

		$this->assertArrayHasKey( $normalized_directory, $choices );
		$this->assertSame( 'nested', $choices[ $normalized_directory ] );

		rmdir( $nested_directory );
		rmdir( $directory );
	}

	public function test_it_registers_song_list_notification_settings() {
		$registered_settings = [];
		$registered_fields = [];

		expect( '__' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'register_setting' )->zeroOrMoreTimes()->andReturnUsing(
			function ( $group, $name ) use ( &$registered_settings ) {
				$registered_settings[] = [ $group, $name ];
				return true;
			}
		);
		expect( 'add_settings_section' )->zeroOrMoreTimes()->andReturn( true );
		expect( 'add_settings_field' )->zeroOrMoreTimes()->andReturnUsing(
			function ( $id ) use ( &$registered_fields ) {
				$registered_fields[] = $id;
				return true;
			}
		);

		\dmbc_extras\register_settings();

		$this->assertContains(
			[ 'settings_group', 'song_list_recipient_roles' ],
			$registered_settings
		);
		$this->assertContains(
			[ 'settings_group', 'song_list_default_recipient' ],
			$registered_settings
		);
		$this->assertContains( 'song_list_recipient_roles', $registered_fields );
		$this->assertContains( 'song_list_default_recipient', $registered_fields );
	}

	public function test_it_saves_and_restores_song_library_exclusion_regexes() {
		$submitted_regexes = "  Archived Music  \n^Practice\n(invalid\n";
		$saved_regexes = \dmbc_extras\sanitize_song_library_exclusion_regexes( $submitted_regexes );

		expect( 'get_option' )
			->once()
			->with( 'song_library_exclusion_regexes', array() )
			->andReturn( $saved_regexes );

		$restored_regexes = \dmbc_extras\get_song_library_exclusion_regexes();

		$this->assertSame(
			array( 'Archived Music', '^Practice' ),
			$saved_regexes
		);
		$this->assertSame( $saved_regexes, $restored_regexes );
	}

	public function test_it_filters_recipient_roles_to_existing_roles() {
		expect( 'wp_roles' )->once()->andReturn(
			(object) array(
				'roles' => array(
					'editor' => array( 'name' => 'Editor' ),
				),
			)
		);
		expect( 'sanitize_key' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return strtolower( (string) $value );
		} );

		$this->assertSame(
			[ 'editor' ],
			\dmbc_extras\sanitize_song_list_recipient_roles( [ 'Editor', 'subscriber' ] )
		);
	}
}
