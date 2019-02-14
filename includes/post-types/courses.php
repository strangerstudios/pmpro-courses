<?php
/**
 * Register Custom Post Type for Courses
 * Hooks into init.
 */
function pmpro_courses_course_cpt() {

	$labels  = array(
		'name'                  => _x( 'PMPro Courses', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => _x( 'PMPro Course', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => __( 'PMPro Courses', 'pmpro-courses' ),
		'name_admin_bar'        => __( 'PMPro Course', 'pmpro-courses' ),
		'archives'              => __( 'Course Archives', 'pmpro-courses' ),
		'attributes'            => __( 'Course Attributes', 'pmpro-courses' ),
		'parent_item_colon'     => __( 'Parent Course:', 'pmpro-courses' ),
		'all_items'             => __( 'Courses Pages', 'pmpro-courses' ),
		'add_new_item'          => __( 'Add New Course', 'pmpro-courses' ),
		'add_new'               => __( 'Add New Course', 'pmpro-courses' ),
		'new_item'              => __( 'New Course', 'pmpro-courses' ),
		'edit_item'             => __( 'Edit Course', 'pmpro-courses' ),
		'update_item'           => __( 'Update Course', 'pmpro-courses' ),
		'view_item'             => __( 'View Course', 'pmpro-courses' ),
		'view_items'            => __( 'View Courses', 'pmpro-courses' ),
		'search_items'          => __( 'Search Course', 'pmpro-courses' ),
		'not_found'             => __( 'PMPro Course not found', 'pmpro-courses' ),
		'not_found_in_trash'    => __( 'PMPro Course not found in Trash', 'pmpro-courses' ),
		'featured_image'        => __( 'Course Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => __( 'Set course featured image', 'pmpro-courses' ),
		'remove_featured_image' => __( 'Remove course featured image', 'pmpro-courses' ),
		'use_featured_image'    => __( 'Use as course featured image', 'pmpro-courses' ),
		'insert_into_item'      => __( 'Insert into course', 'pmpro-courses' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'pmpro-courses' ),
		'items_list'            => __( 'PMPro Courses list', 'pmpro-courses' ),
		'items_list_navigation' => __( 'Courses list navigation', 'pmpro-courses' ),
		'filter_items_list'     => __( 'Filter Courses list', 'pmpro-courses' ),
	);
	$rewrite = array(
		'slug'       => 'course',
		'with_front' => true,
		'pages'      => true,
		'feeds'      => false,
	);
	$args    = array(
		'label'               => __( 'PMPro Course', 'pmpro-courses' ),
		'description'         => __( 'Courses for PMPro Courses', 'pmpro-courses' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'post-formats' ),
		'taxonomies'          => array( 'category', 'post_tag' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'show_in_rest'        => true,
	);
	register_post_type( 'pmpro_course', $args );

}
add_action( 'init', 'pmpro_courses_course_cpt', 0 );

/**
 * Define the metaboxes.
 */
function pmpro_courses_course_cpt_define_meta_boxes() {
	add_meta_box( 'pmpro_page_meta', __( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'pmpro_course', 'side');
	add_meta_box( 'pmpro_courses_lessons', __( 'Lessons', 'pmpro-courses'), 'pmpro_courses_course_cpt_lessons', 'pmpro_course', 'normal' );	
}
add_action('admin_menu', 'pmpro_courses_course_cpt_define_meta_boxes', 20);

/**
 * Callback for lessons meta box
 */
function pmpro_courses_course_cpt_lessons() {
?>
<h2>Lessons</h2>
<p>Copy this stuff from PMPro Series</p>
<?php
}