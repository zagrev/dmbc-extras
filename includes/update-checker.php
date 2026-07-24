<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Puc_v5_Factory' ) ) {
	$plugin_update_checker_path = dirname( __DIR__ ) . '/vendor/autoload.php';

	if ( file_exists( $plugin_update_checker_path ) ) {
		require_once $plugin_update_checker_path;
	}
}

function dmbc_extras_setup_update_checker() {
	if ( ! class_exists( 'Puc_v5_Factory' ) ) {
		return;
	}

	$github_repo = 'https://github.com/zagrev/dmbc-extras';
	$plugin_file = dirname( __DIR__ ) . '/dmbc-extras.php';
	$loader      = Puc_v5_Factory::buildUpdateChecker(
		$github_repo,
		$plugin_file,
		'dmbc-extras'
	);

	if ( method_exists( $loader, 'setBranch' ) ) {
		$loader->setBranch( 'main' );
	}

	if ( method_exists( $loader, 'getVcsApi' ) ) {
		$vcs_api = $loader->getVcsApi();

		if ( method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
			$vcs_api->enableReleaseAssets();
		}
	}
}

function dmbc_extras_allow_automatic_plugin_updates( $should_update, $item ) {
	if ( isset( $item->slug ) && 'dmbc-extras' === $item->slug ) {
		return true;
	}

	return $should_update;
}

add_action( 'admin_init', 'dmbc_extras_setup_update_checker' );
add_filter( 'auto_update_plugin', 'dmbc_extras_allow_automatic_plugin_updates', 10, 2 );
