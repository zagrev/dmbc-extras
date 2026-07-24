<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dmbc_extras_register_song_list_post_type() {
	register_post_type(
		'dmbc_song_list',
		array(
			'labels'          => array(
				'name'          => __( 'Rehearsal Song Lists', 'dmbc-extras' ),
				'singular_name' => __( 'Rehearsal Song List', 'dmbc-extras' ),
				'add_new_item'  => __( 'Add New Rehearsal Song List', 'dmbc-extras' ),
				'edit_item'     => __( 'Edit Rehearsal Song List', 'dmbc-extras' ),
				'new_item'      => __( 'New Rehearsal Song List', 'dmbc-extras' ),
				'view_item'     => __( 'View Rehearsal Song List', 'dmbc-extras' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'supports'        => array( 'title', 'editor', 'revisions' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'menu_icon'       => 'dashicons-list-view',
		)
	);
}
