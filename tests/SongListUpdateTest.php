<?php
namespace dmbc_extras\Tests;
if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class SongListUpdateTest extends DmbcTestCase {
	/**
	 * Test that updating a song list saves the selected songs.
	 */
	public function test_update_saves_selected_songs() {
		$post = (object) array(
			'ID' => 5,
			'post_title' => 'Existing',
			'post_content' => 'Old',
		);
		$expected_song_list = [ '/Song A/Sub Song', '/Song B' ];

		// when( 'wp_unslash' )->returnArg();
		// when( 'sanitize_text_field' )->returnArg();
		// when( 'wp_kses_post' )->returnArg();
		// when( 'absint' )->returnArg();
		// when( 'wp_normalize_path' )->returnArg();
		when( 'clean_post_cache' )->returnArg();
		expect( 'wp_verify_nonce' )->once()->andReturn( true );
		expect( 'current_user_can' )->andReturnUsing(
			function ( $capability ) {
				return in_array( $capability, [ 'edit_song_list', 'manage_options' ], true );
			}
		);
		expect( 'wp_update_post' )
			->once()
			->andReturnUsing( function ( $args, $ignored ) use ( &$post_data ) {
				$post_data = $args;
				return $post_data['ID'] ?? 0;
			} );
		expect( 'add_action' )
			->once()
			->with( 'admin_notices', \Mockery::type( 'callable' ) )
			->andReturnUsing( function ( $hook, $callback ) use ( &$captured_callback ) {
				$captured_callback = $callback;
				return true;
			} );
		expect( 'update_post_meta' )->once()->andReturnUsing(
			function ( $id, $key, $value ) use ( &$actual_metadata ) {
				if ( isset( $actual_metadata[ $key ] ) ) {
					$actual_metadata[ $key ] = $value;
					return true;
				}
				$actual_metadata[ $key ] = $value;
				return $key;
			} );
		expect( 'get_option' )->andReturn( 'dmbc-song-library-test' );

		$_POST = array(
			'dmbc_song_list_nonce' => 'nonce',
			'dmbc_song_list_id' => 5,
			'dmbc_song_list_title' => 'Existing Updated',
			'dmbc_song_list_content' => 'New content',
			'dmbc_song_list_songs' => $expected_song_list,
		);

		\dmbc_extras\dmbc_extras_handle_song_list_form();

		$this->assertArrayHasKey( 'ID', $post_data );
		$this->assertSame( 5, $post_data['ID'] );
		$this->assertArrayHasKey( 'dmbc_song_list_songs', $actual_metadata );
		$this->assertSame( $expected_song_list, $actual_metadata['dmbc_song_list_songs'] );
	}
}
