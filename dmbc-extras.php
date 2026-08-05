<?php
namespace dmbc_extras;
/**
 * Plugin Name: dmbc-extras
 * Plugin URI: https://github.com/zagrev/dmbc-extras
 * Description: Adds custom capabilities and member-focused functionality for the Dayton Metro Barbershop Chorus.
 * Version: 0.1.4
 * Author: Steve Betts
 * Author URI: https://github.com/zagrev
 * Text Domain: dmbc-extras
 * Domain Path: /languages
 * Tested up to: 7.0.2
 * Requires PHP: 8.0 
 * Requires Plugins: 
 * License: CC BY-NC-ND
 * License URI: https://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * dmbc-extras is free software: you can redistribute it and/or modify
 * it under the terms of the CC-BY-NC-ND but you cannot make or sell
 * a commercial product or derivitive commercial product using this code.
 * See https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode.txt
 *
 * dmbc-extras is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file should not be accessed directly.' . PHP_EOL;
	exit;
}

$plugin_dir = plugin_dir_path( __FILE__ );
$plugin_url = plugin_dir_url( __FILE__ );

require_once "$plugin_dir/includes/activate.php";
require_once "$plugin_dir/includes/deactivate.php";
require_once "$plugin_dir/includes/admin/song-list-form-handler.php";
require_once "$plugin_dir/includes/admin/song-lists-page.php";
require_once "$plugin_dir/includes/admin/menu.php";
require_once "$plugin_dir/includes/cpts/song-list-cpt.php";
require_once "$plugin_dir/includes/update-checker.php";

\register_activation_hook( "$plugin_dir/includes/activate.php", __NAMESPACE__ . '\dmbc_extras_activate' );
\register_deactivation_hook( "$plugin_dir/includes/deactivate.php", __NAMESPACE__ . '\dmbc_extras_deactivate' );
\register_uninstall_hook( "$plugin_dir/includes/uninstall.php", __NAMESPACE__ . '\dmbc_extras_uninstall' );

\add_action( 'init', __NAMESPACE__ . '\dmbc_extras_register_song_list_post_type' );
\add_action( 'init', __NAMESPACE__ . '\dmbc_extras_add_custom_capabilities' );
\add_action( 'admin_menu', __NAMESPACE__ . '\dmbc_extras_add_admin_menu' );
\add_action( 'admin_init', __NAMESPACE__ . '\dmbc_extras_handle_song_list_form' );
