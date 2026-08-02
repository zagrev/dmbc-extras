<?php
namespace dmbc_extras\Tests;

use WP_Mock\Arguments;
use WP_Mock;

class SongListFormHandlerTest extends WP_Mock\Tools\TestCase {
	public function test_it_saves_selected_song_folders_as_post_meta() {
		$updated_roles = [];

		WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->once()->withArgs( ['edit_song_list'] )->andReturn( [true] );
		WP_Mock::userFunction( 'current_user_can' )->once()->withArgs( ['manage_options'] )->andReturn( [true] );
		WP_Mock::userFunction( 'update_post_meta' )->once()->andReturns( function ( $args ) use ( &$updated_roles ) {
			$updated_roles[] = $args;
			return [true];
		} );

		// post has no ID, therefore this should create new song list
		$_POST     = [
			'dmbc_song_list_nonce'   => 'nonce',
			'dmbc_song_list_title'   => 'Spring Rehearsal',
			'dmbc_song_list_content' => 'Notes',
			'dmbc_song_list_songs'   => [
				WP_CONTENT_DIR . '/dmbc-song-library/Song A',
				WP_CONTENT_DIR . '/dmbc-song-library/Song A/Sub Song',
				WP_CONTENT_DIR . '/dmbc-song-library/Song B',
			]
		];
		$post_data = [];
		WP_Mock::userFunction( 'wp_insert_post' )
			->once()
			->andReturns( function ( $args, $ignored ) use ( &$post_data ) {
				$post_data = $args;
				return true;
			} );
		WP_Mock::userFunction( 'add_action' )
			->once()
			->andReturns( function ( $hook, $callback ) use ( &$captured_callback ) {
				$captured_callback = $callback;
				return true;
			} );

		dmbc_extras_handle_song_list_form();

		// expecting a post like this:
		// $post_data = array(
		// 	'ID'           => $song_list_id,
		// 	'post_type'    => 'dmbc_song_list',
		// 	'post_title'   => $title,
		// 	'post_content' => $content,
		// 	'post_status'  => 'publish',
		// );

		$this->assertEquals( 0, $post_data['ID'] );
		$this->assertEquals( $_POST['dmbc_song_list_title'], $post_data['post_title'] );
		$this->assertEquals( $_POST['dmbc_song_list_content'], $post_data['post_content'] );
		$this->assertArraysAreEqual( $_POST['dmbc_song_list_songs'], $post_data['meta_input']['dmbc_song_list_songs'] );

		$this->assertIsCallable( $captured_callback, 'The registered callback is not callable.' );
		ob_start();
		$captured_callback();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'created', $output );

		$this->assertArrayHasKey( 'meta_input', $post_data );
		$this->assertArraysAreEqual( $_POST['dmbc_song_list_songs'], $post_data['meta_input']['dmbc_song_list_songs'] );

		WP_Mock::assertHooksAdded();
	}

	public function test_it_grants_the_edit_song_list_capability_to_the_administrator_role() {
		$role = new class() {
			public $capabilities = array();

			public function add_cap( $cap ) {
				$this->capabilities[ $cap ] = true;
			}
		};

		WP_Mock::userFunction( 'get_role' )->once()->withArgs( ['administrator'] )->andReturn( $role );

		dmbc_extras_add_custom_capabilities();

		$this->assertTrue( $role->capabilities['edit_song_list'] );
		$this->assertEquals( 2, $role->capabilities['edit_song_list'] );

		WP_Mock::assertHooksAdded();
	}
}
