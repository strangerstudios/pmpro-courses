<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
			'label' => esc_html__( 'Course Categories', 'pmpro-courses' ),
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
 * @since 2.0
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

		// Get the current settings for course outline/sections. If this is empty, let's create a blank array with dummy data.
		$sections = get_post_meta( get_the_ID(), 'pmpro_course_sections', true ) ?: array( array( 'section_id' => 1, 'section_name' => '', 'lessons' => array() ) );

		// Let's also try to get lessons that may be missing from the 'lessons' for backwards compatibility and run on page load.
		$all_lessons_for_course = array_map( 'intval', wp_list_pluck( pmpro_courses_get_lessons( get_the_ID() ), 'ID' ) );

		// Collect all lessons currently in ALL sections
		$existing = array();
		foreach ( $sections as $section ) {
			if ( isset( $section['lessons'] ) && is_array( $section['lessons'] ) ) {
				$existing = array_merge( $existing, array_map( 'intval', $section['lessons'] ) );
			}
		}

		$missing_lessons = array_values( array_diff( $all_lessons_for_course, $existing ) );

		// Let's just insert missing lessons into the first section. There will always be one section.
		if ( $missing_lessons ) {
			if ( empty( $sections[0]['lessons'] ) || ! is_array( $sections[0]['lessons'] ) ) {
				$sections[0]['lessons'] = array();
			}
			$sections[0]['lessons'] = array_values( array_unique( array_merge( $sections[0]['lessons'], $missing_lessons ) ) );
		}
		
		// Build the full list of lesson IDs assigned across all sections of this course.
		// This is passed to each section's dropdown so that lessons already used in any
		// section are excluded from every section's "Add Lesson" select.
		$all_assigned_lessons = array();
		foreach ( $sections as $section ) {
			if ( isset( $section['lessons'] ) && is_array( $section['lessons'] ) ) {
				$all_assigned_lessons = array_merge( $all_assigned_lessons, array_map( 'intval', $section['lessons'] ) );
			}
		}
		$all_assigned_lessons = array_values( array_unique( $all_assigned_lessons ) );

		// Callback points to a DOM template for the Course Outline/Sections.
		?>
		<div class="pmpro_admin">
			<?php
				foreach( $sections as $section ) {
					pmpro_courses_get_sections_html( $section, $all_assigned_lessons );
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
		</div> <!-- .pmpro_admin -->
		<?php
}

// Get the course lesson table for a section.
function pmpro_courses_get_lessons_table_html( $lessons, $section_id = 1 ){
	ob_start();
	foreach ( $lessons as $lesson ) {
		$lesson_id  = intval( $lesson->ID );
		$section_id = intval( $section_id );
		?>
		<tr data-lesson_id="<?php echo esc_attr( $lesson_id ); ?>">
			<td class="pmpro-sort-handle"><span class="dashicons dashicons-menu" aria-hidden="true"></span></td>
			<td>
				<a href="<?php echo esc_url( add_query_arg( array( 'post' => $lesson_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) ); ?>" title="<?php echo esc_attr__( 'Edit', 'pmpro-courses' ) . ' ' . esc_attr( $lesson->post_title ); ?>" target="_blank">
					<?php echo esc_html( $lesson->post_title ) . " (#" . esc_html( $lesson_id ) . ")"; ?>
				</a>
				<?php
					if ( $lesson->post_status === 'draft' ) {
						echo ' &mdash; ' . esc_html__( 'Draft', 'pmpro-courses' );
					}
				?>
				<input type="hidden" name="pmpro_courses_lessons[<?php echo esc_attr( $section_id ); ?>][]" value="<?php echo esc_attr( $lesson_id ); ?>" />
			</td>
			<td class="pmpro-courses-lesson-remove">
				<a class="button button-secondary" href="javascript:pmpro_courses_remove_lesson(<?php echo esc_attr( $lesson_id ); ?>, <?php echo esc_attr( $section_id ); ?>); void(0);">
					<?php esc_html_e( 'Remove', 'pmpro-courses' ); ?>
				</a>
			</td>
		</tr>
		<?php
	}
	$ret = ob_get_clean();
	return $ret;
}

/**
 * Save PMPro Course sections + lessons as a normalized array.
 * Runs only when saving the pmpro_course post type.
 * 
 * @since 2.0
 */
function pmpro_courses_save_course_sections( $post_id, $post, $update ) {

	// Let's not save if the user cannot edit the current post.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Let's not save during autosave, to save some resources.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Bail if the nonce has failed.
	if ( empty( $_POST['pmpro_course_sections_nonce'] ) || ! wp_verify_nonce( $_POST['pmpro_course_sections_nonce'], 'pmpro_course_sections_save' ) ) {
		return;
	}

	// Get all the data.
	$section_names         = isset( $_POST['pmpro_course_lessons_section_name'] ) ? (array) $_POST['pmpro_course_lessons_section_name'] : array();
	$section_ids   = isset( $_POST['pmpro_course_lessons_section_id'] )   ? (array) $_POST['pmpro_course_lessons_section_id']   : array();
	$lessons_by_id = isset( $_POST['pmpro_courses_lessons'] )             ? (array) $_POST['pmpro_courses_lessons']             : array();

	$section_names = array_map( 'sanitize_text_field', $section_names );
	$section_ids   = array_map( 'absint', $section_ids );

	$sections         = array();
	$all_lesson_ids   = array();   // union across all sections, helps order the lessons in post object for next/previous navigation.

	// Loop through all sections to be processed.
	foreach ( $section_ids as $key => $section_id ) {
		if ( ! $section_id ) {
			continue;
		} 

		$section_name = isset( $section_names[ $key ] ) ? sanitize_text_field( $section_names[ $key ] ) : '';
		$raw_lessons  = isset( $lessons_by_id[ $section_id ] ) ? (array) $lessons_by_id[ $section_id ] : array();
		$lesson_ids = array_values( array_unique( array_filter( array_map( 'absint', $raw_lessons ) ) ) );

		// Skip totally empty unnamed sections.
		if ( $section_name === '' && empty( $lesson_ids ) ) {
			continue;
		}

		$sections[] = array(
			'section_id'   => $section_id,
			'section_name' => $section_name,
			'lessons'      => $lesson_ids,
		);

		// Accumulate union (preserving per-section order first; course order computed later).
		foreach ( $lesson_ids as $lid ) {
			$all_lesson_ids[] = $lid;
		}
	}

	// Nothing to save, empty sections.
	if ( empty( $sections ) ) {
		return;
	}

	// Get all lesson IDs that are passed through $_POST to see if any need to be removed from the post parent.
	$all_lesson_ids = array_values( array_unique( array_filter( $all_lesson_ids, 'absint' ) ) );

	// Get all pre-existing lessons for a this course.
	$existing_lessons = get_children( array(
		'post_parent' => $post_id,
		'post_type'   => 'pmpro_lesson',
		'post_status' => 'any',
		'fields'      => 'ids',
	) );

	// Remove any lessons from the post parent if they're removed from the sections and missing from the $_POST.
	$lessons_to_remove = array_diff( (array) $existing_lessons, $all_lesson_ids );
	if ( ! empty( $lessons_to_remove ) ) {
		foreach ( $lessons_to_remove as $lesson_remove_id ) {
			wp_update_post( array(
				'ID'          => $lesson_remove_id,
				'post_parent' => 0,
			) );
		}
	}

	// Loop through all lessons for a course, and update their post parent and menu order.
	$lesson_order   = 1;
	foreach ( $all_lesson_ids as $lid ) {
		wp_update_post( array(
			'ID'          => $lid,
			'post_parent' => $post_id,
			'menu_order'  => $lesson_order++,
		) );
	}

	// Save the post meta data, this is primarily used for the settings.
	update_post_meta( $post_id, 'pmpro_course_sections', $sections );
}
add_action( 'save_post_pmpro_course', 'pmpro_courses_save_course_sections', 20, 3 ); // run a bit later