<?php
namespace dmbc_extras\Tests;

use WP_Mock;

class SongListAdminPageTest extends WP_Mock\Tools\TestCase {
	public function test_it_lists_available_song_folders_from_wp_content_directory() {
		$post = [
			'ID'           => 1,
			'post_title'   => 'Test List',
			'post_content' => 'Content',
		];

		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_post_meta' )->with( $post )->andReturn( array( 'Song A' ) );

		WP_Mock::userFunction( 'get_posts' )->with( [ 'numberposts' => -1 ] )->andReturn( [ $post ] );

		$_GET['dmbc_song_sort']    = 'modified';
		$_GET['dmbc_song_list_id'] = 1;

		ob_start();
		dmbc_extras_render_song_lists_admin_page();
		$output = ob_get_clean();

		// verify that this page structure is correct
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
		// $this->assertStringContainsString( 'Test List', $output );
	}
}
