<?php

/**
 * Register Custom Post Type for lessons
 * Hooks into init.
 */
function pmpro_courses_lesson_cpt() {

	$labels  = array(
		'name'                  => _x( 'PMPro Lessons', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => _x( 'PMPro Lesson', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => __( 'PMPro Lessons', 'pmpro-courses' ),
		'name_admin_bar'        => __( 'PMPro Lesson', 'pmpro-courses' ),
		'archives'              => __( 'Lesson Archives', 'pmpro-courses' ),
		'attributes'            => __( 'Lesson Attributes', 'pmpro-courses' ),
		'parent_item_colon'     => __( 'Parent Lesson:', 'pmpro-courses' ),
		'all_items'             => __( 'Lessons', 'pmpro-courses' ),
		'add_new_item'          => __( 'Add New Lesson', 'pmpro-courses' ),
		'add_new'               => __( 'Add New Lesson', 'pmpro-courses' ),
		'new_item'              => __( 'New Lesson', 'pmpro-courses' ),
		'edit_item'             => __( 'Edit Lesson', 'pmpro-courses' ),
		'update_item'           => __( 'Update Lesson', 'pmpro-courses' ),
		'view_item'             => __( 'View Lesson', 'pmpro-courses' ),
		'view_items'            => __( 'View Lessons', 'pmpro-courses' ),
		'search_items'          => __( 'Search Lesson', 'pmpro-courses' ),
		'not_found'             => __( 'PMPro Lesson not found', 'pmpro-courses' ),
		'not_found_in_trash'    => __( 'PMPro Lesson not found in Trash', 'pmpro-courses' ),
		'featured_image'        => __( 'Lesson Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => __( 'Set lesson featured image', 'pmpro-courses' ),
		'remove_featured_image' => __( 'Remove lesson featured image', 'pmpro-courses' ),
		'use_featured_image'    => __( 'Use as lesson featured image', 'pmpro-courses' ),
		'insert_into_item'      => __( 'Insert into lesson', 'pmpro-courses' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'pmpro-courses' ),
		'items_list'            => __( 'PMPro Lessons list', 'pmpro-courses' ),
		'items_list_navigation' => __( 'Lessons list navigation', 'pmpro-courses' ),
		'filter_items_list'     => __( 'Filter Lessons list', 'pmpro-courses' ),
	);
	$rewrite = array(
		'slug'       => 'lessons',
		'with_front' => true,
		'pages'      => true,
		'feeds'      => false,
	);
	$args    = array(
		'label'               => __( 'PMPro Lesson', 'pmpro-courses' ),
		'description'         => __( 'Lessons for PMPro Courses', 'pmpro-courses' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'post-formats' ),
		'taxonomies'          => array( 'category', 'post_tag' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=pmpro_course',
		'menu_position'       => 1,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'post',
		'show_in_rest'        => true,
	);
	register_post_type( 'pmpro_lesson', $args );

}
add_action( 'init', 'pmpro_courses_lesson_cpt', 0 );
