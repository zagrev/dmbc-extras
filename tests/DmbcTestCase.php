<?php
namespace dmbc_extras\Tests;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DmbcTestCase extends TestCase {
	use MockeryPHPUnitIntegration;

	protected $starting_level;

	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->starting_level = ob_get_level();
	}

	public function tearDown(): void {
		if ( ob_get_level() > $this->starting_level ) {
			ob_end_clean();
		}
		Monkey\tearDown();
		parent::tearDown();
	}
}
