<?php
namespace dmbc_extras\Tests;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;


class SongListFormHandlerTest extends DmbcTestCase {
	/**
	 * Test that selected song folders are saved as post meta.
	 */
	public function test_it_saves_selected_song_folders_as_post_meta() {
		$actual_metadata = [];
		$post_data = [];
		$captured_callback = null;

		expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'sanitize_text_field' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'absint' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'clean_post_cache' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'get_option' )->zeroOrMoreTimes()->andReturn( 'dmbc-song-library' );
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
			'dmbc_song_list_rehearsal_date' => '2026-09-15',
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
		expect( 'esc_html__' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );

		\dmbc_extras\handle_song_list_form();

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
		}
		else {
			$captured_callback();
		}
		$output = ob_get_clean();
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'created', $output );

		$this->assertArraysAreEqual( $_POST['dmbc_song_list_songs'], $actual_metadata['dmbc_song_list_songs'], 'The saved post meta does not match the selected songs.' );
		$this->assertSame( '2026-09-15', $actual_metadata['dmbc_song_list_rehearsal_date'] );
	}

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
		$post_data = [];
		$actual_metadata = [];
		$captured_callback = null;

		expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'sanitize_text_field' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'absint' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'clean_post_cache' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'get_option' )->zeroOrMoreTimes()->andReturn( 'dmbc-song-library' );
		expect( 'wp_verify_nonce' )->andReturn( true );
		expect( 'current_user_can' )->once()->with( 'edit_song_list' )->andReturn( true );

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
		expect( 'update_post_meta' )->zeroOrMoreTimes()->andReturnUsing(
			function ( $id, $key, $value ) use ( &$actual_metadata ) {
				$actual_metadata[ $key ] = $value;
				return true;
			}
		);
		expect( 'esc_html__' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );
		expect( 'esc_html_' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );
		expect( 'esc_html_e' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			echo $text;
			return true;
		} );
		expect( 'esc_html' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );

		$_POST = array(
			'dmbc_song_list_nonce' => 'nonce',
			'dmbc_song_list_id' => 5,
			'dmbc_song_list_title' => 'Existing Updated',
			'dmbc_song_list_content' => 'New content',
			'dmbc_song_list_rehearsal_date' => '2026-10-20',
			'dmbc_song_list_songs' => $expected_song_list,
		);

		\dmbc_extras\handle_song_list_form();

		$this->assertArrayHasKey( 'ID', $post_data );
		$this->assertSame( 5, $post_data['ID'] );
		$this->assertArrayHasKey( 'dmbc_song_list_songs', $actual_metadata );
		$this->assertSame( $expected_song_list, $actual_metadata['dmbc_song_list_songs'] );
		$this->assertSame( '2026-10-20', $actual_metadata['dmbc_song_list_rehearsal_date'] );
	}

	/**
	 * Test that a song list is emailed to users in the requested role.
	 */
	public function test_it_sends_a_song_list_to_users_in_a_role() {
		$recipients = [];
		$subject = '';
		$message = '';

		expect( 'get_post' )
			->once()
			->with( 12 )
			->andReturn(
				(object) array(
					'ID' => 12,
					'post_type' => 'dmbc_song_list',
					'post_title' => 'Spring Rehearsal',
					'post_content' => 'Review the new entrances.',
				)
			);
		expect( 'get_users' )
			->once()
			->with( [ 'role__in' => [ 'chorus_member' ] ] )
			->andReturn(
				[
					(object) array( 'user_email' => 'member@example.com' ),
					(object) array( 'user_email' => '' ),
				]
			);
		global $__dmbc_test_post_meta;
		$__dmbc_test_post_meta[12]['dmbc_song_list_songs'] = [ '/Song A', '/Song B' ];
		expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
			return 'dmbc_song_list_rehearsal_date' === $key ? '2026-09-15' : [ '/Song A', '/Song B' ];
		} );
		expect( 'wp_mail' )
			->once()
			->andReturnUsing(
				function ( $to, $mail_subject, $mail_message ) use ( &$recipients, &$subject, &$message ) {
					$recipients = $to;
					$subject = $mail_subject;
					$message = $mail_message;
					return true;
				}
			);

		$this->assertTrue( \dmbc_extras\send_song_list_to_role( 12, 'chorus_member' ) );
		$this->assertSame( [ 'member@example.com' ], $recipients );
		$this->assertSame( 'Rehearsal song list: Spring Rehearsal', $subject );
		$this->assertStringContainsString( 'Rehearsal date: 2026-09-15', $message );
		$this->assertStringContainsString( 'Review the new entrances.', $message );
		$this->assertStringContainsString( '/Song A', $message );
		$this->assertStringContainsString( '/Song B', $message );
	}

	public function test_it_deletes_a_song_list_when_requested() {
		$captured_callback = null;

		expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'sanitize_text_field' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'absint' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
			return $value;
		} );
		expect( 'wp_verify_nonce' )->once()->with( 'delete-nonce', 'dmbc_delete_song_list' )->andReturn( true );
		expect( 'current_user_can' )->andReturnUsing( function ( $capability ) {
			return in_array( $capability, [ 'edit_song_list', 'manage_options' ], true );
		} );
		expect( 'wp_delete_post' )->once()->with( 42, true )->andReturn( true );
		expect( 'add_action' )
			->once()
			->with( 'admin_notices', \Mockery::type( 'callable' ) )
			->andReturnUsing( function ( $hook, $callback ) use ( &$captured_callback ) {
				$captured_callback = $callback;
				return true;
			} );
		expect( 'esc_html__' )->zeroOrMoreTimes()->andReturnUsing( function ( $text ) {
			return $text;
		} );

		$_POST = array(
			'dmbc_delete_song_list' => '1',
			'dmbc_song_list_id' => 42,
			'dmbc_song_list_delete_nonce' => 'delete-nonce',
		);

		\dmbc_extras\handle_song_list_form();

		$this->assertNotNull( $captured_callback, 'The delete admin notice callback was not registered.' );
		ob_start();
		$captured_callback();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'deleted', $output );
	}
}
