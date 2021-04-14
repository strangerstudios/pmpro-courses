<?php
/**
 * Register Custom Post Type for Courses
 * Hooks into init.
 */
function pmpro_courses_course_cpt() {

	$labels  = array(
		'name'                  => _x( 'Courses', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => _x( 'Course', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => __( 'Courses', 'pmpro-courses' ),
		'name_admin_bar'        => __( 'Course', 'pmpro-courses' ),
		'archives'              => __( 'Course Archives', 'pmpro-courses' ),
		'attributes'            => __( 'Course Attributes', 'pmpro-courses' ),
		'all_items'             => __( 'All Courses', 'pmpro-courses' ),
		'add_new_item'          => __( 'Add New Course', 'pmpro-courses' ),
		'add_new'               => __( 'Add New Course', 'pmpro-courses' ),
		'new_item'              => __( 'New Course', 'pmpro-courses' ),
		'edit_item'             => __( 'Edit Course', 'pmpro-courses' ),
		'update_item'           => __( 'Update Course', 'pmpro-courses' ),
		'view_item'             => __( 'View Course', 'pmpro-courses' ),
		'view_items'            => __( 'View Courses', 'pmpro-courses' ),
		'search_items'          => __( 'Search Courses', 'pmpro-courses' ),
		'not_found'             => __( 'Course not found', 'pmpro-courses' ),
		'not_found_in_trash'    => __( 'Course not found in Trash', 'pmpro-courses' ),
		'featured_image'        => __( 'Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => __( 'Set course featured image', 'pmpro-courses' ),
		'remove_featured_image' => __( 'Remove featured image', 'pmpro-courses' ),
		'use_featured_image'    => __( 'Use as course featured image', 'pmpro-courses' ),
		'insert_into_item'      => __( 'Insert into course', 'pmpro-courses' ),
		'uploaded_to_this_item' => __( 'Uploaded to this course', 'pmpro-courses' ),
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
		'label'               => __( 'Course', 'pmpro-courses' ),
		'description'         => __( 'Courses and lessons for members.', 'pmpro-courses' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		'hierarchical'        => true,
		'public'              => true,
		'menu_icon'           => 'dashicons-book',
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => 'courses',
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'show_in_rest'        => true,
	);
	register_post_type( 'pmpro_course', $args );
	
	// Register Category Taxonomy
	register_taxonomy(
	    'pmpro_course_category',
	    'pmpro_course',
	    array(
			'label' => __( 'Course Categories' ),
			'rewrite' => array( 'slug' => 'course-category' ),
		    'hierarchical' => true,
			'show_in_rest' => true,
	    )
 	);
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
		global $wpdb, $post;

		// boot out people without permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		?>	
		<div class="message error"><p><?php //echo $this->error; ?></p></div>
		<table id="pmproc_table" class="wp-list-table widefat striped pmpro-metabox-items">
			<thead>
				<th><?php _e( 'Order', 'pmpro-courses' ); ?></th>
				<th width="50%"><?php _e( 'Title', 'pmpro-courses' ); ?></th>
				<th width="20%"><?php _e( 'Actions', 'pmpro-courses' ); ?></th>
			</thead>
			<tbody>
			<?php 				
				echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $post->ID ) );
			?>
			</tbody>
		</table>

		<h3><?php _e( 'Add Lessons', 'pmpro-courses' ); ?> <?php /*<a class="button button-secondary" href="<?php echo admin_url( 'post-new.php?post_type=pmpro_lesson' ); ?>" target="_blank"><?php _e( 'Create New Lesson','pmpro-courses' ); ?></a> */ ?></h3>
		<table id="newmeta" class="wp-list-table pmpro-metabox-items">
			<tbody>
				<tr>
					<td>
						<label for="pmproc_post"><?php _e( 'Lesson', 'pmpro-courses' ); ?></label>
						<select id="pmproc_post" name="pmproc_post">
							<option value=""></option>
							<?php
								$all_lessons = get_posts( array( 'post_type' => 'pmpro_lesson', 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
								foreach ( $all_lessons as $lesson ) {
									?>
									<option value="<?php echo intval( $lesson->ID ); ?>"><?php echo esc_html( $lesson->post_title ); ?>
									(#<?php echo $lesson->ID;?>)
									</option>
									<?php
								}
							?>
						</select>
					</td>
					<td width="20%">
						<label for="pmproc_order"><?php _e( 'Order', 'pmpro-courses' ); ?></label>
						<input id="pmproc_order" name="pmproc_order" type="text" size="5" />
					</td>
					<td width="20%">
						<a class="button button-primary" id="pmproc_save"><?php _e( 'Add to Course', 'pmpro-courses' ); ?></a>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	// }
}