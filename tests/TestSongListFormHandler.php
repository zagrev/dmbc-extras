<?php
require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

class TestSongListFormHandler extends TestCase {
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
}
