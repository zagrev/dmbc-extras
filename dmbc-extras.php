<?php
/**
 * Plugin Name: dmbc-extras
 * Plugin URI: https://github.com/zagrev/dmbc-extras
 * Description: Adds custom capabilities and member-focused functionality for the Dayton Metro Barbershop Chorus.
 * Version: 0.1.0
 * Author: Steve Betts
 * Author URI: https://github.com/zagrev
 * Text Domain: dmbc-extras
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Update URI: https://github.com/zagrev/dmbc-extras
 * Requires Plugins: yahnis-elsts/plugin-update-checker
 * License: CC BY-NC-ND
 * License URI: https://creativecommons.org/licenses/by-nc-nd/4.0/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_notices',
	function () {
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'dmbc-extras is active and ready to add custom capabilities for the Dayton Metro Barbershop Chorus.', 'dmbc-extras' ) . '</p></div>';
	}
);

require_once __DIR__ . '/includes/admin/song-list-form-handler.php';
require_once __DIR__ . '/includes/admin/song-lists-page.php';
require_once __DIR__ . '/includes/admin/menu.php';
require_once __DIR__ . '/includes/cpts/song-list-cpt.php';
require_once __DIR__ . '/includes/update-checker.php';

add_action( 'init', 'dmbc_extras_register_song_list_post_type' );
add_action( 'admin_menu', 'dmbc_extras_add_admin_menu' );
add_action( 'admin_init', 'dmbc_extras_handle_song_list_form' );
