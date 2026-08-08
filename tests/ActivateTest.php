<?php
namespace dmbc_extras\Tests;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;

class ActivateTest extends DmbcTestCase {
	/**
	 * Test that the edit_song_list capability is granted to the administrator and editor roles.
	 */
	public function test_it_grants_the_edit_song_list_capability_to_the_administrator_role() {
		$admin_role = new \MockRole( 'administrator' );
		$editor_role = new \MockRole( 'editor' );

		expect( 'get_role' )->andReturnUsing(
			function ( $role ) use ( $admin_role, $editor_role ) {
				if ( 'administrator' === $role ) {
					return $admin_role;
				}
				if ( 'editor' === $role ) {
					return $editor_role;
				}
				return null;
			}
		);

		\dmbc_extras\dmbc_extras_add_custom_capabilities();

		$expected_role_name = 'edit_song_list';
		$this->assertTrue(
			$admin_role->capabilities[ $expected_role_name ] === [ 'administrator' ],
			'The administrator role should have the edit_song_list capability after calling dmbc_extras_add_custom_capabilities().'
		);
		$this->assertTrue(
			$editor_role->capabilities[ $expected_role_name ] === [ 'editor' ],
			'The editor role should have the edit_song_list capability after calling dmbc_extras_add_custom_capabilities().'
		);
	}
}
