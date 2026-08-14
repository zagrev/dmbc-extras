<?php
namespace dmbc_extras;

if ( ! defined( 'ABSPATH' ) ) {
	print 'ABSPATH is not defined. This file (' . __FILE__ . ') should not be accessed directly.' . PHP_EOL;
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	$path = rtrim( ABSPATH, '/' ) . '/wp-admin/includes/class-wp-list-table.php';
	require_once $path;
}

use WP_List_Table;

class SongListTable extends WP_List_Table {
	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'rehearsal_date' => __( 'Rehearsal Date', 'dmbc-extras' ),
			'name' => __( 'Name', 'dmbc-extras' ),
			'songs' => __( 'Songs', 'dmbc-extras' )
		);
	}

	// Specific renderer for the title column
	public function column_name( $item ) {
		return $item->post_title;
	}

	public function column_rehearsal_date( $item ) {
		$actions = array(
			'edit' => sprintf( '<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->id ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->id ),
		);
		// Return rehearsal date with row actions
		$base_url = \is_admin() ? \admin_url( 'admin.php?page=dmbc-rehearsal-song-lists' ) : \get_permalink();
		$view_url = \add_query_arg( array( 'song_list_id' => $item->ID ), $base_url );
		return sprintf( '<a href="%1$s">%2$s</a> %3$s', \esc_url( $view_url ), $item->dmbc_song_list_rehearsal_date, $this->row_actions( $actions ) );
	}



	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-items[]" value="%s" />',
			esc_attr( $item->ID ) // Pass the unique ID of the row
		);
	}

	// Fallback renderer for all other columns
	public function column_songs( $item ) {
		$metadata = \get_post_meta( $item->ID, 'dmbc_song_list_songs', false );
		return is_array( $metadata ) ? implode( ', ', $metadata[0] ) : $metadata;
	}

	public function get_sortable_columns() {
		return array(
			'rehearsal_date' => array( 'rehearsal_date', true ),
			'name' => array( 'name', false )
		);
	}

	public function prepare_items() {
		// 1. Define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// 2. Fetch your raw data (usually via $wpdb or an API)

		$song_lists = \get_posts(
			[
				'post_type' => 'dmbc_song_list',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby' => 'meta_value',
				'order' => 'DESC',
				'meta_key' => 'dmbc_song_list_rehearsal_date',
			]
		);
		// print_r( "Songs:" );
		// print_r( $song_lists );

		// 3. Define total counts and pagination configuration
		$per_page = 10;
		$current_page = $this->get_pagenum();
		$total_items = count( $song_lists );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page
		) );

		// 4. Assign data to items array
		$this->items = $song_lists;
	}

}
