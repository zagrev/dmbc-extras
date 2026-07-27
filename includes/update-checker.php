<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Puc_v5_Factory' ) ) {
	$plugin_update_checker_path = $plugin_dir . '/vendor/autoload.php';

	if ( file_exists( $plugin_update_checker_path ) ) {
		require_once $plugin_update_checker_path;
	}
}

function dmbc_extras_setup_update_checker() {
	global $plugin_dir;

	if ( ! class_exists( 'Puc_v5_Factory' ) ) {
		require $plugin_dir . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

		if ( ! class_exists( 'Puc_v5_Factory' ) ) {
			return;
		}
	}

	$github_repo = 'https://github.com/zagrev/dmbc-extras';
	$plugin_file = $plugin_dir . '/dmbc-extras.php';

	$updateChecker = Puc_v5_Factory::buildUpdateChecker(
		$github_repo,
		$plugin_file,
		'dmbc-extras'
	);

	$updateChecker->setBranch( 'main' );

	// if ( method_exists( $updateChecker, 'getVcsApi' ) ) {
	// 	$vcs_api = $updateChecker->getVcsApi();

	// 	if ( method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
	// 		$vcs_api->enableReleaseAssets();
	// 	}
	// }
}

function dmbc_extras_allow_automatic_plugin_updates( $should_update, $item ) {
	if ( isset( $item->slug ) && 'dmbc-extras' === $item->slug ) {
		return true;
	}

	return $should_update;
}

add_action( 'admin_init', 'dmbc_extras_setup_update_checker' );
add_filter( 'auto_update_plugin', 'dmbc_extras_allow_automatic_plugin_updates', 10, 2 );
