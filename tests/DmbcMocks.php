<?php
if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\stubEscapeFunctions;
use function Brain\Monkey\Functions\stubtranslationFunctions;
use function Brain\Monkey\Functions\when;

stubEscapeFunctions();
stubtranslationFunctions();

if ( ! class_exists( 'MockRole' ) ) {
	class MockRole {
		public $name;
		public $capabilities = [];

		/**
		 * Create a mock role instance.
		 *
		 * @param string $role The role name.
		 */
		public function __construct( $role ) {
			$this->name = $role;
		}

		/**
		 * Add or remove a capability from the role.
		 *
		 * @param string $cap The capability name.
		 * @param bool   $grant Whether to grant or revoke the capability.
		 */
		public function add_cap( $cap, $grant = true ) {
			if ( ! isset( $this->capabilities[ $cap ] ) ) {
				$this->capabilities[ $cap ] = [];
			}
			if ( $grant ) {
				$this->capabilities[ $cap ][] = $this->name;
			} else {
				$this->capabilities[ $cap ] = array_filter( $this->capabilities[ $cap ], function ( $role ) {
					return $role !== $this->name;
				} );
			}
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID;
		public $post_title;
		public $post_content;
		public $post_excerpt;

		/**
		 * Create a mock WP_Post instance.
		 *
		 * @param array $args Post data arguments.
		 */
		public function __construct( $args ) {
			$this->ID = $args['ID'] ?? 0;
			$this->post_title = $args['post_title'] ?? '';
			$this->post_content = $args['post_content'] ?? '';
			$this->post_excerpt = $args['post_excerpt'] ?? '';
		}

		/**
		 * Create a mock WP_Post instance using the provided array.
		 *
		 * @param mixed $args Description for $args.
		 */
		public static function create( $args ) {
			return new self( $args );
		}
	}
}

// mock basic WordPress functions used in the plugin
when( 'plugin_dir_path' )->justReturn( dirname( __DIR__ ) . '/' );

when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/dmbc-extras/' );

expect( 'register_activation_hook' )->zeroOrMoreTimes()->andReturnUsing( function ( $file, $callback ) {
	print "register_activation_hook: '$file' => '$callback'" . PHP_EOL;
	return true;
} );

expect( 'register_deactivation_hook' )->zeroOrMoreTimes()->andReturnUsing( function ( $file, $callback ) {
	print "register_deactivation_hook: '$file' => '$callback'" . PHP_EOL;
	return true;
} );

expect( 'register_uninstall_hook' )->zeroOrMoreTimes()->andReturnUsing( function ( $file, $callback ) {
	print "register_uninstall_hook: '$file' => '$callback'" . PHP_EOL;
	return true;
} );

expect( 'sanitize_text_field' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( fn( $value ) => str_replace( '\\', '/', $value ) );

expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'absint' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'get_the_title' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
	if ( $post instanceof \WP_Post ) {
		return $post->post_title;
	}
	return $post['post_title'];
} );

expect( 'get_the_excerpt' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
	if ( $post instanceof \WP_Post ) {
		return $post->post_excerpt;
	}
	if ( isset( $post['post_excerpt'] ) ) {
		return $post['post_excerpt'];
	} else if ( isset( $post['post_content'] ) ) {
		return $post['post_content'];
	}
	return '';
} );

expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key ) {
	global $__dmbc_test_post_meta;
	if ( isset( $__dmbc_test_post_meta[ $post_id ] ) && isset( $__dmbc_test_post_meta[ $post_id ][ $key ] ) ) {
		return [ $__dmbc_test_post_meta[ $post_id ][ $key ] ];
	}
	return [ [] ];
} );

expect( 'set_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $value ) {
	global $__dmbc_test_post_meta;
	$__dmbc_test_post_meta[ $post_id ][ $key ] = $value;
	return true;
} );

expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
	return str_replace( '\\', '/', $path );
} );

expect( 'wp_nonce_field' )->zeroOrMoreTimes()->andReturn( '<input type="hidden" name="dmbc_song_list_nonce" value="1" />' );
expect( 'wp_create_nonce' )->zeroOrMoreTimes()->andReturn( 'dmbc-nonce' );

expect( 'submit_button' )->zeroOrMoreTimes()->andReturnUsing( function ( $text, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = '' ) {
	print "submit_button: text='$text', type='$type', name='$name', wrap='$wrap', other_attributes='$other_attributes'" . PHP_EOL;
	return "<button type='submit' name='$name' class='$type'>$text</button>";
} );
expect( 'selected' )->zeroOrMoreTimes()->andReturnUsing( function ( $selected, $current ) {
	return $selected === $current ? 'selected' : '';
} );
expect( 'admin_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
	return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
} );

expect( '__' )->zeroOrMoreTimes()->andReturnFirstArg();

// these add_action calls get us through the plugin initialization to the unit tests`
expect( 'add_action' )->zeroOrMoreTimes()->with( 'init', \Mockery::type( 'callable' ) )->andReturn( true );
expect( 'add_action' )->zeroOrMoreTimes()->with( 'admin_menu', \Mockery::type( 'callable' ) )->andReturn( true );
expect( 'add_action' )->zeroOrMoreTimes()->with( 'admin_init', \Mockery::type( 'callable' ) )->andReturn( true );

when( 'add_filter' )->justReturn( true );
when( "clean_post_cache" )->justReturn( true );
when( 'get_option' )->justReturn( 'dmbc-song-library' );

