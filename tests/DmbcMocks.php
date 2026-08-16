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
			}
			else {
				$this->capabilities[ $cap ] = array_filter( $this->capabilities[ $cap ], function ( $role ) {
					return $role !== $this->name;
				} );
			}
		}

		/**
		 * Create a mock role instance.
		 *
		 * @param string $role The role name.
		 */
		public function __construct( $role ) {
			$this->name = $role;
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
		 * Create a mock WP_Post instance using the provided array.
		 *
		 * @param mixed $args Description for $args.
		 */
		public static function create( $args ) {
			return new self( $args );
		}

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
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	class WP_List_Table {
		public $items = [];
		public $_columns = [];

		public function get_columns() {
			return $this->_columns;
		}
		public function row_actions( $item ) {
			return 'action=';
		}
		public function display() {
			foreach ( $this->items as $item ) {
				foreach ( $this->get_columns() as $column_name => $attributes ) {
					if ( \method_exists( $this, 'column_' . $column_name ) ) {
						echo \call_user_func( array( $this, 'column_' . $column_name ), $item );
					}
					else {
						echo $this->column_default( $item, $column_name );
					}
				}
			}
		}
		public function column_default( $item, $column_name ) {
			return isset( $item->$column_name ) ? $item->$column_name : '';
		}
		public function prepare_items() {
			$this->items = \get_posts();
		}
		public function get_items() {
			return $this->items;
		}
		// 		public function set_items( $items ) {
// 			$this->items = $items;
// 		}
		public function get_pagenum() {
			return 1;
		}
		// 		public function get_pagination_args() {
// 			return [];
// 		}
		public function set_pagination_args( $args ) {
		}
		// 	}
	}
}

// mock basic WordPress functions used in the plugin
expect( 'absint' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( '__' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'add_action' )->zeroOrMoreTimes()->with( 'admin_init', \Mockery::type( 'callable' ) )->andReturn( true );
expect( 'add_action' )->zeroOrMoreTimes()->with( 'admin_menu', \Mockery::type( 'callable' ) )->andReturn( true );
expect( 'add_action' )->zeroOrMoreTimes()->with( 'init', \Mockery::type( 'callable' ) )->andReturn( true );

when( 'add_filter' )->justReturn( true );
when( 'add_shortcode' )->justReturn( true );

expect( 'admin_url' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
	return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
} );

when( "clean_post_cache" )->justReturn( true );

expect( 'get_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $single = false ) {
	global $__dmbc_test_post_meta;
	if ( isset( $__dmbc_test_post_meta[ $post_id ] ) && isset( $__dmbc_test_post_meta[ $post_id ][ $key ] ) ) {
		return $single ? $__dmbc_test_post_meta[ $post_id ][ $key ] : [ $__dmbc_test_post_meta[ $post_id ][ $key ] ];
	}
	return $single ? null : [ [] ];
} );

when( 'is_user_logged_in' )->justReturn( true );

expect( 'current_time' )->zeroOrMoreTimes()->andReturnUsing( function ( $type = 'mysql', $gmt = 0 ) {
	return '2026-08-12 12:00:00';
} );

expect( 'esc_html' )->zeroOrMoreTimes()->andReturnFirstArg();
expect( 'esc_html__' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'get_the_excerpt' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
	if ( $post instanceof \WP_Post ) {
		return $post->post_excerpt;
	}
	if ( isset( $post['post_excerpt'] ) ) {
		return $post['post_excerpt'];
	}
	else if ( isset( $post['post_content'] ) ) {
		return $post['post_content'];
	}
	return '';
} );

expect( 'get_the_title' )->zeroOrMoreTimes()->andReturnUsing( function ( $post ) {
	if ( $post instanceof \WP_Post ) {
		return $post->post_title;
	}
	return $post['post_title'];
} );

expect( 'get_the_date' )->zeroOrMoreTimes()->andReturnUsing( function ( $format, $post ) {
	return $post->post_date ?? '';
} );

expect( 'get_the_modified_date' )->zeroOrMoreTimes()->andReturnUsing( function ( $format, $post ) {
	return $post->post_modified ?? '';
} );

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

expect( 'selected' )->zeroOrMoreTimes()->andReturnUsing( function ( $selected, $current ) {
	return $selected === $current ? 'selected' : '';
} );

expect( 'set_post_meta' )->zeroOrMoreTimes()->andReturnUsing( function ( $post_id, $key, $value ) {
	global $__dmbc_test_post_meta;
	$__dmbc_test_post_meta[ $post_id ][ $key ] = $value;
	return true;
} );

expect( 'submit_button' )->zeroOrMoreTimes()->andReturnUsing( function ( $text, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = '' ) {
	print "submit_button: text='$text', type='$type', name='$name', wrap='$wrap', other_attributes='$other_attributes'" . PHP_EOL;
	return "<button type='submit' name='$name' class='$type'>$text</button>";
} );

expect( 'wp_create_nonce' )->zeroOrMoreTimes()->andReturn( 'dmbc-nonce' );

expect( 'wp_json_encode' )->zeroOrMoreTimes()->andReturnUsing( function ( $value ) {
	return json_encode( $value );
} );

expect( 'wp_parse_args' )->zeroOrMoreTimes()->andReturnUsing( function ( $args, $defaults = array () ) {
	if ( is_array( $args ) ) {
		return array_merge( $defaults, $args );
	}
	return $defaults;
} );

expect( 'wp_kses_post' )->zeroOrMoreTimes()->andReturnFirstArg();

expect( 'wp_nonce_field' )->zeroOrMoreTimes()->andReturn( '<input type="hidden" name="dmbc_song_list_nonce" value="1" />' );

expect( 'wp_normalize_path' )->zeroOrMoreTimes()->andReturnUsing( function ( $path ) {
	return str_replace( '\\', '/', $path );
} );

expect( 'wp_unslash' )->zeroOrMoreTimes()->andReturnUsing( fn( $value ) => str_replace( '\\', '/', $value ) );

