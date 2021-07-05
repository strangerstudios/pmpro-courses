<?php

/**
 * Register Custom Post Type for lessons
 * Hooks into init.
 */
function pmpro_courses_lesson_cpt() {

	$labels  = array(
		'name'                  => _x( 'Lessons', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => _x( 'Lesson', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => __( 'Lessons', 'pmpro-courses' ),
		'name_admin_bar'        => __( 'Lesson', 'pmpro-courses' ),
		'archives'              => __( 'Lesson Archives', 'pmpro-courses' ),
		'attributes'            => __( 'Lesson Attributes', 'pmpro-courses' ),
		'all_items'             => __( 'All Lessons', 'pmpro-courses' ),
		'add_new_item'          => __( 'Add New Lesson', 'pmpro-courses' ),
		'add_new'               => __( 'Add New Lesson', 'pmpro-courses' ),
		'new_item'              => __( 'New Lesson', 'pmpro-courses' ),
		'edit_item'             => __( 'Edit Lesson', 'pmpro-courses' ),
		'update_item'           => __( 'Update Lesson', 'pmpro-courses' ),
		'view_item'             => __( 'View Lesson', 'pmpro-courses' ),
		'view_items'            => __( 'View Lessons', 'pmpro-courses' ),
		'search_items'          => __( 'Search Lesson', 'pmpro-courses' ),
		'not_found'             => __( 'Lesson not found', 'pmpro-courses' ),
		'not_found_in_trash'    => __( 'Lesson not found in Trash', 'pmpro-courses' ),
		'featured_image'        => __( 'Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => __( 'Set lesson featured image', 'pmpro-courses' ),
		'remove_featured_image' => __( 'Remove featured image', 'pmpro-courses' ),
		'use_featured_image'    => __( 'Use as lesson featured image', 'pmpro-courses' ),
		'insert_into_item'      => __( 'Insert into lesson', 'pmpro-courses' ),
		'uploaded_to_this_item' => __( 'Uploaded to this lesson', 'pmpro-courses' ),
		'items_list'            => __( 'PMPro Lessons list', 'pmpro-courses' ),
		'items_list_navigation' => __( 'Lessons list navigation', 'pmpro-courses' ),
		'filter_items_list'     => __( 'Filter Lessons list', 'pmpro-courses' ),
	);
	$rewrite = array(
		'slug'       => 'lesson',
		'with_front' => true,
		'pages'      => true,
		'feeds'      => false,
	);
	$args    = array(
		'label'               => __( 'Lesson', 'pmpro-courses' ),
		'description'         => __( 'Lessons for Paid Memberships Pro Courses', 'pmpro-courses' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=pmpro_course',
		'menu_position'       => 1,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'show_in_rest'        => true,
	);
	register_post_type( 'pmpro_lesson', $args );

}
add_action( 'init', 'pmpro_courses_lesson_cpt' );

/**
 * Add meta boxes for lessons.
 */
function pmpro_courses_lessons_cpt_define_meta_boxes() {
	add_meta_box( 'pmpro_courses_lesson_attributes', __( 'Course', 'pmpro-courses'), 'pmpro_courses_lesson_course_metabox', 'pmpro_lesson', 'side', 'high' );
}
add_action('admin_menu', 'pmpro_courses_lessons_cpt_define_meta_boxes', 20);

/**
 * Lesson attributes meta box.
 */
function pmpro_courses_lesson_course_metabox( $post ) {	
	?>
	<label class="components-base-control__label" for="pmpro_courses_parent"><?php _e( 'Course', 'pmpro-courses' );?></label>
	<?php	
	wp_dropdown_pages( array(
		'selected' => $post->post_parent,
		'echo' => 1,
		'show_option_none' => '--' . __( 'None', 'pmpro-courses' ) . '--',
		'name' => 'pmpro_courses_parent',
		'id' => 'pmpro_courses_parent',
		'post_type' => 'pmpro_course',
		'post_status' => 'publish',
	) );
}

/**
 * Save meta data for lessons from the edit lesson page.
 */
function pmpro_courses_save_lessons_meta( $post_id ){
	if( 'pmpro_lesson' === get_post_type() ){
		wp_update_post(
			array( 'ID' => $post_id,
			'post_parent' => intval( $_REQUEST['pmpro_courses_parent'] )
			)
		);
	}
}
add_action( 'save_post', 'pmpro_courses_save_lessons_meta', 10, 1 );