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
		'slug'       => pmpro_courses_unique_rewrite_slug( 'course' ),
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
		'has_archive'         => get_option( 'pmpro_courses_cpt_archive', 1 ) ? 'courses' : false,
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
add_action( 'init', 'pmpro_courses_course_cpt', 30 );

/**
 * Define the metaboxes.
 */
function pmpro_courses_course_cpt_define_meta_boxes() {
	if ( function_exists( 'pmpro_page_meta' ) ) {
		add_meta_box( 'pmpro_page_meta', esc_html__( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'pmpro_course', 'side');
	}
	add_meta_box( 'pmpro_courses_lessons', esc_html__( 'Course Outline', 'pmpro-courses'), 'pmpro_courses_course_cpt_lessons', 'pmpro_course', 'normal' );	
}
add_action('admin_menu', 'pmpro_courses_course_cpt_define_meta_boxes', 20);

/**
 * Always show the "Course Outline" metabox on a page for PMPro Courses as it's required.
 *
 * @since TBD
 */
function pmpro_courses_unhide_course_outline_meta_box( $hidden, $screen ) {
	if ( $screen->post_type == 'pmpro_course' ) {
		$hidden = array_diff( $hidden, array( 'pmpro_courses_lessons' ) );
	}
	return $hidden;
}
add_filter('hidden_meta_boxes', 'pmpro_courses_unhide_course_outline_meta_box', 10, 2);


/**
 * Callback for lessons meta box
 */
function pmpro_courses_course_cpt_lessons() {
		// boot out people without permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		
		// Generate an array of values:
		$sections = get_post_meta( get_the_ID(), 'pmpro_course_sections', true );

		// Loop through all options and then show each section, we'll get there.///
		// Callback points to a DOM template for the Course Outline/Sections.
		foreach( $sections as $section ) {
			// Fake the section stuff.
			pmpro_courses_get_sections_html( $section );
		}
		?>
		<p class="text-center">
			<button id="pmpro_courses_add_section" name="pmpro_courses_add_section" class="button button-primary button-hero">
				<?php
					echo '<span class="dashicons dashicons-plus"></span>' . ' ' . esc_html__( 'Add New Section', 'pmpro-courses' );
				?>
			</button>			
		</p>
		<input type="hidden" name="pmpro_course_sections_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmpro_course_sections_save' ) ); ?>" />
		<?php
}

// Get the course lesson table for a section.
// Maybe useful for initial load IDK?
function pmpro_courses_get_lessons_table_html( $lessons, $section_id = 1 ){

	if ( ! empty( $lessons ) ) {
		
		$ret = '';

		foreach ( $lessons as $lesson ) {

			$ret .= "<tr data-lesson_id='" . intval( $lesson->ID ) . "'>";
			$ret .= "<td class='pmpro-lesson-order'>" . esc_html( $lesson->menu_order) . "</td>";
			$ret .= "<td><a href='".admin_url( 'post.php?post=' . esc_attr( intval( $lesson->ID ) ) . '&action=edit' ) . "' title='" . esc_attr__('Edit', 'pmpro-courses') .' '. esc_attr( $lesson->post_title ). "' target='_BLANK'>". esc_html( $lesson->post_title ) ."</a></td>";
			$ret .= "<input type='hidden' name='pmpro_courses_lessons[" . intval( $section_id ) . "][]' value='". intval( $lesson->ID ) ."' />";
			$ret .= "<td>";
			$ret .= "<a class='button button-secondary' href='javascript:pmpro_courses_edit_post(" . intval( $lesson->ID ) . "," . intval( $lesson->menu_order ) . "); void(0);'>". esc_html__( 'edit', 'pmpro-courses' )."</a>";
			$ret .= " ";
			$ret .= "<a class='button button-secondary' href='javascript:pmpro_courses_remove_post(". intval( $lesson->ID ) ."); void(0);'>". esc_html__( 'remove', 'pmpro-courses' )."</a>";
			$ret .= "</td>";
			$ret .= "</tr>";
		}

	} 

	return $ret;

}

/**
 * Save PMPro Course sections + lessons as a normalized array.
 * Runs only when saving the pmpro_course post type.
 * 
 * @since TBD
 */
function pmpro_courses_save_course_sections( $post_id, $post, $update ) {
	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Let's make sure we have the nonce in the post.
	if ( empty( $_POST['pmpro_course_sections_nonce'] ) || ! wp_verify_nonce( $_POST['pmpro_course_sections_nonce'], 'pmpro_course_sections_save' ) ) {
		return;
	}

	// Gather raw inputs.
	$names		   = isset( $_POST['pmpro_course_lessons_section_name'] ) ? (array) $_POST['pmpro_course_lessons_section_name'] : array();
	$ids		   = isset( $_POST['pmpro_course_lessons_section_id'] )   ? (array) $_POST['pmpro_course_lessons_section_id']   : array();
	$lessons_by_id = isset( $_POST['pmpro_courses_lessons'] )			  ? (array) $_POST['pmpro_courses_lessons']			    : array();

	// Normalize + sanitize.
	$names = array_map( 'sanitize_text_field', $names );
	$ids   = array_map( 'absint', $ids );

	// Build normalized sections array following the submitted order of IDs.
	$sections = array();
	foreach ( $ids as $i => $sid ) {
		// Skip any invalid section IDs.
		if ( ! $sid ) {
			continue;
		}

		$section_name = isset( $names[ $i ] ) ? $names[ $i ] : '';
		$raw_lessons  = isset( $lessons_by_id[ $sid ] ) ? (array) $lessons_by_id[ $sid ] : array();

		// Sanitize lesson IDs and keep order; remove empties/duplicates.
		$lesson_ids = array_values( array_unique( array_filter( array_map( 'absint', $raw_lessons ) ) ) );

		// Skip empty sections with empty lessons.
		if ( $section_name === '' && empty( $lesson_ids ) ) {
			continue;
		}

		// Set the post parent for each lesson ID.
		/// Leave this here for now.
		foreach ( $lesson_ids as $lesson_id ) {
			wp_update_post( array(
				'ID'		  => $lesson_id,
				'post_parent' => $post_id,
			) );
		}

		$sections[] = array(
			'section_id'   => $sid,
			'section_name' => $section_name,
			'lessons'	   => $lesson_ids,
		);
	}

	// Save the post meta from the meta box.
	if ( ! empty( $sections ) ) {
		update_post_meta( $post_id, 'pmpro_course_sections', $sections );
	}
}
add_action( 'save_post_pmpro_course', 'pmpro_courses_save_course_sections', 10, 3 );