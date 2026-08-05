<?php
namespace dmbc_extras\Tests;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class MockRole {
	public $name;
	public $capabilities = [];

	/**
	 * Create a mock role instance.
	 *
	 * @param string $role The role name.
	 */
	public function __construct( $role ) {
		$this->name = $role;
	}

	/**
	 * Add or remove a capability from the role.
	 *
	 * @param string $cap The capability name.
	 * @param bool   $grant Whether to grant or revoke the capability.
	 */
	public function add_cap( $cap, $grant = true ) {
		if ( ! isset( $this->capabilities[ $cap ] ) ) {
			$this->capabilities[ $cap ] = [];
		}
		if ( $grant ) {
			$this->capabilities[ $cap ][] = $this->name;
		} else {
			$this->capabilities[ $cap ] = array_filter( $this->capabilities[ $cap ], function ( $role ) {
				return $role !== $this->name;
			} );
		}
	}
}

class SongListFormHandlerTest extends DmbcTestCase {
	/**
	 * Test that selected song folders are saved as post meta.
	 */
	public function test_it_saves_selected_song_folders_as_post_meta() {
		$actual_metadata = [];
		$post_data = [];
		$captured_callback = null;

		when( 'wp_unslash' )->returnArg();
		expect( 'wp_verify_nonce' )->once()->andReturn( true );
		expect( 'current_user_can' )->andReturnUsing(
			function ( $capability ) {
				return in_array( $capability, [ 'edit_song_list', 'manage_options' ], true );
			}
		);
		expect( 'update_post_meta' )->andReturnUsing(
			function ( $id, $key, $value ) use ( &$actual_metadata ) {
				$actual_metadata[ $key ] = $value;
				return true;
			}
		);

		// post has no ID, therefore this should create new song list
		$_POST = [
			'dmbc_song_list_nonce' => 'nonce',
			'dmbc_song_list_title' => 'Spring Rehearsal',
			'dmbc_song_list_content' => 'Notes',
			'dmbc_song_list_songs' => [
				'/Song A',
				'/Song A/Sub Song',
				'/Song B',
			]
		];
		expect( 'wp_insert_post' )
			->once()
			->andReturnUsing( function ( $args, $ignored ) use ( &$post_data ) {
				$post_data = $args;
				return 123;
			} );
		expect( 'add_action' )
			->andReturnUsing( function ( $hook, $callback ) use ( &$captured_callback ) {
				if ( 'admin_notices' === $hook ) {
					$captured_callback = $callback;
				}
				return true;
			} );

		\dmbc_extras\dmbc_extras_handle_song_list_form();

		// expecting a post like this:
		// $post_data = array(
		// 	'ID'           => $song_list_id,
		// 	'post_type'    => 'dmbc_song_list',
		// 	'post_title'   => $title,
		// 	'post_content' => $content,
		// 	'post_status'  => 'publish',
		// );

		$this->assertSame( 0, $post_data['ID'] );
		$this->assertSame( $_POST['dmbc_song_list_title'], $post_data['post_title'] );
		$this->assertSame( $_POST['dmbc_song_list_content'], $post_data['post_content'] );

		$this->assertNotNull( $captured_callback, 'The admin notice callback was not registered.' );
		$this->assertTrue(
			is_callable( $captured_callback ) || is_string( $captured_callback ),
			'The registered callback is not callable.'
		);
		ob_start();
		if ( is_string( $captured_callback ) ) {
			call_user_func( $captured_callback );
		} else {
			$captured_callback();
		}
		$output = ob_get_clean();
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'created', $output );

		$this->assertArraysAreEqual( $_POST['dmbc_song_list_songs'], $actual_metadata['dmbc_song_list_songs'], 'The saved post meta does not match the selected songs.' );
	}


	/**
	 * Test that the edit_song_list capability is granted to the administrator and editor roles.
	 */

	public function test_it_grants_the_edit_song_list_capability_to_the_administrator_role() {
		$adminRole = new MockRole( 'administrator' );
		$editorRole = new MockRole( 'editor' );

		expect( 'get_role' )->andReturnUsing(
			function ( $role ) use ( $adminRole, $editorRole ) {
				if ( 'administrator' === $role ) {
					return $adminRole;
				}
				if ( 'editor' === $role ) {
					return $editorRole;
				}
				return null;
			}
		);

		\dmbc_extras\dmbc_extras_add_custom_capabilities();

		$expected_role_name = 'edit_song_list';
		$this->assertTrue(
			$adminRole->capabilities[ $expected_role_name ] === [ 'administrator' ],
			'The administrator role should have the edit_song_list capability after calling dmbc_extras_add_custom_capabilities().'
		);
		$this->assertTrue(
			$editorRole->capabilities[ $expected_role_name ] === [ 'editor' ],
			'The editor role should have the edit_song_list capability after calling dmbc_extras_add_custom_capabilities().'
		);
	}
}
