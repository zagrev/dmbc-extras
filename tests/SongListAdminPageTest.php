<?php
namespace dmbc_extras\Tests;

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

		when( 'wp_unslash' )->returnArg();
		when( 'sanitize_text_field' )->returnArg();
		when( 'absint' )->returnArg();
		when( 'wp_normalize_path' )->returnArg();
		expect( 'get_post' )->zeroOrMoreTimes()->andReturn( $post );
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturn( [ 'Song A', 'Song B' ] );
		expect( 'get_posts' )->zeroOrMoreTimes()->andReturn( [ $post ] );
		expect( 'get_the_title' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_object ) {
			return $post_object->post_title ?? 'Test List';
		} );
		expect( 'get_the_excerpt' )->zeroOrMoreTimes()->andReturn( 'Content' );
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
		expect( 'esc_url' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_html' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'esc_textarea' )->zeroOrMoreTimes()->andReturnFirstArg();
		expect( 'get_option' )->zeroOrMoreTimes()->andReturn( 'dmbc-song-library' );

		$_GET['dmbc_song_sort'] = 'modified';
		$_GET['dmbc_song_list_id'] = 1;

		ob_start();
		\dmbc_extras\dmbc_extras_render_song_lists_admin_page();
		$output = ob_get_clean();

		// verify that this page structure is correct
		assertTrue( true );
		$this->assertStringContainsString( 'Rehearsal Song Lists', $output );
		$this->assertStringContainsString( 'Select Songs', $output );
		$this->assertStringContainsString( 'Add Selected', $output );
		$this->assertStringContainsString( 'Remove Selected', $output );
		$this->assertStringContainsString( 'Clear All', $output );
		$this->assertStringContainsString( 'Move Up', $output );
		$this->assertStringContainsString( 'Move Down', $output );
		$this->assertStringContainsString( 'dmbc_selected_song_folders', $output );
		$this->assertStringContainsString( 'Sort by', $output );
		$this->assertStringContainsString( 'Song A', $output );
		$this->assertStringContainsString( 'Update Song List', $output );
	}

	public function test_it_returns_the_configured_song_library_directory() {
		expect( 'get_option' )->once()->with( 'dmbc_extras_song_library_directory', 'dmbc-song-library' )->andReturn( 'custom-library' );

		$this->assertSame( 'custom-library', \dmbc_extras\dmbc_extras_get_song_library_directory_option() );
	}

	public function test_it_lists_subdirectories_for_the_wp_content_browser() {
		$directory = sys_get_temp_dir() . '/dmbc-extras-browser-' . uniqid( '', true );
		$nested_directory = $directory . '/nested';
		mkdir( $nested_directory, 0777, true );

		when( 'wp_normalize_path' )->returnArg();

		$choices = \dmbc_extras\dmbc_extras_get_wp_content_folder_choices( $directory );
		$normalized_directory = str_replace( '\\', '/', $nested_directory );

		$this->assertArrayHasKey( $normalized_directory, $choices );
		$this->assertSame( 'nested', $choices[ $normalized_directory ] );

		rmdir( $nested_directory );
		rmdir( $directory );
	}
}
