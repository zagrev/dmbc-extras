<?php
require_once __DIR__ . '/bootstrap.php';

use DmbcExtras\TestCase;

class SongListFormHandlerTest extends TestCase {
	public function test_it_saves_selected_song_folders_as_post_meta() {
		$_POST = array(
			'dmbc_song_list_nonce'   => 'nonce',
			'dmbc_song_list_title'   => 'Spring Rehearsal',
			'dmbc_song_list_content' => 'Notes',
			'dmbc_song_list_songs'   => array(
				WP_CONTENT_DIR . '/dmbc-song-library/Song A/Sub Song',
			),
		);

		dmbc_extras_handle_song_list_form();

		$this->assertArrayHasKey( 'meta_input', $GLOBALS['__dmbc_test_inserted_post'] );
		$this->assertSame( array( 'Sub Song' ), $GLOBALS['__dmbc_test_inserted_post']['meta_input']['dmbc_song_list_songs'] );
		$this->assertStringContainsString( '- Sub Song', $GLOBALS['__dmbc_test_inserted_post']['post_content'] );
	}

	public function test_it_grants_the_edit_song_list_capability_to_the_administrator_role() {
		$GLOBALS['__dmbc_test_roles']['administrator'] = new class() {
			public $capabilities = array();

			public function add_cap( $cap ) {
				$this->capabilities[ $cap ] = true;
			}
		};

		dmbc_extras_add_custom_capabilities();

		$this->assertTrue( $GLOBALS['__dmbc_test_roles']['administrator']->capabilities['edit_song_list'] );
	}
}
