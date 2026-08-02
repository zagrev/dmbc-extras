<?php
namespace dmbc_extras\Tests;

use WP_Mock;

class SongListUpdateTest extends WP_Mock\Tools\TestCase {
	public function test_update_saves_selected_songs() {
		$post = (object) array(
			'ID'           => 5,
			'post_title'   => 'Existing',
			'post_content' => 'Old',
		);

		WP_Mock::userFunction( 'get_post', array( 'args' => array( 5 ), 'return' => $post ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'args' => array( 5, 'dmbc_song_list_songs' ), 'return' => array( array( 'OldSong' ) ) ) );

		$_POST = array(
			'dmbc_song_list_nonce'   => 'nonce',
			'dmbc_song_list_id'      => 5,
			'dmbc_song_list_title'   => 'Existing Updated',
			'dmbc_song_list_content' => 'New content',
			'dmbc_song_list_songs'   => array(
				WP_CONTENT_DIR . '/dmbc-song-library/Song A/Sub Song',
				WP_CONTENT_DIR . '/dmbc-song-library/Song B',
			),
		);

		dmbc_extras_handle_song_list_form();

		$this->assertArrayHasKey( 'dmbc_song_list_songs', $GLOBALS['__dmbc_test_post_meta'][5] );
		$this->assertSame( array( 'Sub Song', 'Song B' ), $GLOBALS['__dmbc_test_post_meta'][5]['dmbc_song_list_songs'] );
		$this->assertArrayHasKey( 'ID', $GLOBALS['__dmbc_test_updated_post'] );
		$this->assertSame( 5, $GLOBALS['__dmbc_test_updated_post']['ID'] );
	}
}
