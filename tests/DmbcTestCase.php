<?php
namespace dmbc_extras\Tests;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\expect;

class DmbcTestCase extends TestCase {
	use MockeryPHPUnitIntegration;

	protected $starting_level = 0;
	protected $song_list_directory = '';

	public function setUp(): void {
		global $plugin_dir;

		parent::setUp();
		Monkey\setUp();

		$this->starting_level = ob_get_level();

		$this->song_list_directory = str_replace( '\\', '/', "$plugin_dir/test-song-lists" );
		$this->create_test_song_list_directory();
		$this->create_song_list_mocks();
	}

	public function tearDown(): void {
		while ( ob_get_level() > $this->starting_level ) {
			ob_end_clean();
		}
		Monkey\tearDown();
		parent::tearDown();
	}

	public function create_test_song_list_directory(): void {
		$dir = $this->song_list_directory;

		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0755, true );
			mkdir( "$dir/Song A", 0755, true );
			mkdir( "$dir/Song B", 0755, true );
			// some files in the song list directory to ensure we just get the directories and not the files
			file_put_contents( "$dir/Song A/song-a.txt", "This is Song A." );
			file_put_contents( "$dir/Song B/song-b.txt", "This is Song B." );
		}
	}

	public function create_song_list_mocks(): void {
		expect( 'get_option' )
			->zeroOrMoreTimes()
			->with( 'song_library_directory', 'dmbc-song-library' )
			->andReturn( $this->song_list_directory );
	}
}
