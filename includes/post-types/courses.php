<?php
/**
 * Register Custom Post Type for Courses
 * Hooks into init.
 */
function pmpro_courses_course_cpt() {

	$labels  = array(
		'name'                  => esc_html_x( 'Courses', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => esc_html_x( 'Course', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => esc_html__( 'Courses', 'pmpro-courses' ),
		'name_admin_bar'        => esc_html__( 'Course', 'pmpro-courses' ),
		'archives'              => esc_html__( 'Course Archives', 'pmpro-courses' ),
		'attributes'            => esc_html__( 'Course Attributes', 'pmpro-courses' ),
		'all_items'             => esc_html__( 'All Courses', 'pmpro-courses' ),
		'add_new_item'          => esc_html__( 'Add New Course', 'pmpro-courses' ),
		'add_new'               => esc_html__( 'Add New Course', 'pmpro-courses' ),
		'new_item'              => esc_html__( 'New Course', 'pmpro-courses' ),
		'edit_item'             => esc_html__( 'Edit Course', 'pmpro-courses' ),
		'update_item'           => esc_html__( 'Update Course', 'pmpro-courses' ),
		'view_item'             => esc_html__( 'View Course', 'pmpro-courses' ),
		'view_items'            => esc_html__( 'View Courses', 'pmpro-courses' ),
		'search_items'          => esc_html__( 'Search Courses', 'pmpro-courses' ),
		'not_found'             => esc_html__( 'Course not found', 'pmpro-courses' ),
		'not_found_in_trash'    => esc_html__( 'Course not found in Trash', 'pmpro-courses' ),
		'featured_image'        => esc_html__( 'Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => esc_html__( 'Set course featured image', 'pmpro-courses' ),
		'remove_featured_image' => esc_html__( 'Remove featured image', 'pmpro-courses' ),
		'use_featured_image'    => esc_html__( 'Use as course featured image', 'pmpro-courses' ),
		'insert_into_item'      => esc_html__( 'Insert into course', 'pmpro-courses' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this course', 'pmpro-courses' ),
		'items_list'            => esc_html__( 'PMPro Courses list', 'pmpro-courses' ),
		'items_list_navigation' => esc_html__( 'Courses list navigation', 'pmpro-courses' ),
		'filter_items_list'     => esc_html__( 'Filter Courses list', 'pmpro-courses' ),
	);
	$rewrite = array(
		'slug'       => 'course',
		'with_front' => true,
		'pages'      => true,
		'feeds'      => false,
	);
	$args    = array(
		'label'               => esc_html__( 'Course', 'pmpro-courses' ),
		'description'         => esc_html__( 'Courses and lessons for members.', 'pmpro-courses' ),
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
			'label' => esc_html__( 'Course Categories' ),
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
	if ( function_exists( 'pmpro_page_meta' ) ) {
		add_meta_box( 'pmpro_page_meta', esc_html__( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'pmpro_course', 'side');
	}
	add_meta_box( 'pmpro_courses_lessons', esc_html__( 'Lessons', 'pmpro-courses'), 'pmpro_courses_course_cpt_lessons', 'pmpro_course', 'normal' );	
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
		<table id="pmpro_courses_table" class="wp-list-table widefat striped pmpro-metabox-items">
			<thead>
				<th><?php esc_html_e( 'Order', 'pmpro-courses' ); ?></th>
				<th width="50%"><?php esc_html_e( 'Title', 'pmpro-courses' ); ?></th>
				<th width="20%"><?php esc_html_e( 'Actions', 'pmpro-courses' ); ?></th>
			</thead>
			<tbody>
			<?php 				
				echo pmpro_courses_get_lessons_table_html( pmpro_courses_get_lessons( $post->ID ) );
			?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Add Lessons', 'pmpro-courses' ); ?></h3>
		<table id="newmeta" class="wp-list-table pmpro-metabox-items">
			<tbody>
				<tr>
					<td>
						<label for="pmpro_courses_post"><?php esc_html_e( 'Lesson', 'pmpro-courses' ); ?></label>
						<select id="pmpro_courses_post" name="pmpro_courses_post">
							<option value=""></option>
							<?php
								$all_lessons = get_posts( array( 'post_type' => 'pmpro_lesson', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
								foreach ( $all_lessons as $lesson ) {
									?>
									<option value="<?php echo intval( $lesson->ID ); ?>"><?php esc_html_e( $lesson->post_title ); ?>
									(#<?php echo $lesson->ID;?>)
									</option>
									<?php
								}
							?>
						</select>
					</td>
					<td width="20%">
						<label for="pmpro_courses_order"><?php esc_html_e( 'Order', 'pmpro-courses' ); ?></label>
						<input id="pmpro_courses_order" name="pmpro_courses_order" type="text" size="5" />
					</td>
					<td width="20%">
						<a class="button button-primary" id="pmpro_courses_save"><?php esc_html_e( 'Add to Course', 'pmpro-courses' ); ?></a>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	// }
}

function pmpro_courses_get_lessons_table_html( $lessons ){

	$ret = "";

	if( !empty( $lessons ) ){
		
		$count = 1;

		foreach ( $lessons as $lesson ) {

			$ret .= "<tr>";
			$ret .= "<td>" . esc_html( $lesson->menu_order) . "</td>";
			$ret .= "<td><a href='".admin_url( 'post.php?post=' . intval( $lesson->ID ) . '&action=edit' ) . "' title='" . esc_attr__('Edit', 'pmpro-courses') .' '. esc_attr( $lesson->post_title ). "' target='_BLANK'>". esc_html( $lesson->post_title ) ."</a></td>";
			$ret .= "<td>";
			$ret .= "<a class='button button-secondary' href='javascript:pmpro_courses_edit_post(" . intval( $lesson->ID ) . "," . intval( $lesson->menu_order ) . "); void(0);'>". esc_html__( 'edit', 'pmpro-courses' )."</a>";
			$ret .= " ";
			$ret .= "<a class='button button-secondary' href='javascript:pmpro_courses_remove_post(". intval( $lesson->ID ) ."); void(0);'>". esc_html__( 'remove', 'pmpro-courses' )."</a>";
			$ret .= "</td>";
			$ret .= "</tr>";

			$count++;
		}

	} 

	return $ret;

}
