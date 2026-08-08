<?php
namespace dmbc_extras;

if ( ! \defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

if ( ! class_exists( 'Puc_v5_Factory' ) ) {
	$plugin_update_checker_path = $plugin_dir . '/vendor/autoload.php';

	if ( file_exists( $plugin_update_checker_path ) ) {
		require_once $plugin_update_checker_path;
	}
}

use YahnisElsts\PluginUpdateChecker\v5p7\PucFactory;

function dmbc_extras_setup_update_checker() {
	global $plugin_dir;

	$github_repo = 'https://github.com/zagrev/dmbc-extras';
	$plugin_file = $plugin_dir . '/dmbc-extras.php';

	$updateChecker = PucFactory::buildUpdateChecker(
		$github_repo,
		$plugin_file,
		'dmbc-extras'
	);

	$updateChecker->setBranch( 'main' );
}

function dmbc_extras_allow_automatic_plugin_updates( $should_update, $item ) {
	if ( isset( $item->slug ) && 'dmbc-extras' === $item->slug ) {
		return true;
	}

	return $should_update;
}

\add_action( 'admin_init', __NAMESPACE__ . '\dmbc_extras_setup_update_checker' );
\add_filter( 'auto_update_plugin', __NAMESPACE__ . '\dmbc_extras_allow_automatic_plugin_updates', 10, 2 );
