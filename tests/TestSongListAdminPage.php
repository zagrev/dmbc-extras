<?php
require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

class TestSongListAdminPage extends TestCase {
	public function test_it_lists_available_song_folders_from_wp_content_directory() {
		$GLOBALS['__dmbc_test_posts'] = array(
			(object) array(
				'ID'           => 1,
				'post_title'   => 'Test List',
				'post_content' => 'Content',
			),
		);

		$GLOBALS['__dmbc_test_post_lookup'] = array(
			1 => (object) array(
				'ID'           => 1,
				'post_title'   => 'Test List',
				'post_content' => 'Content',
			),
		);

		$GLOBALS['__dmbc_test_post_meta'] = array(
			1 => array(
				'dmbc_song_list_songs' => array( 'Song A' ),
			),
		);

		$_GET['dmbc_song_sort']    = 'modified';
		$_GET['dmbc_song_list_id'] = 1;

		ob_start();
		dmbc_extras_render_song_lists_admin_page();
		$output = ob_get_clean();

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
		$this->assertStringContainsString( 'Test List', $output );
	}
}
