<?php
namespace dmbc_extras\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends \PHPUnit\Framework\TestCase {
	// Integrates Mockery assertions safely with PHPUnit
	use MockeryPHPUnitIntegration;

	/**
	 * Set up Brain Monkey before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Clean up Brain Monkey and Mockery variables after each test
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
