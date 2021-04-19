<?php 
/**
 * Content filter to show additional course information on the single course page.
 */
function pmpro_courses_the_content_course( $filtered_content, $original_content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {
		// Look for a /paid-memberships-pro/pmpro-courses/lessons.php template in the active theme.
		$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
		$custom_file = $custom_dir."lessons.php";

		// Load custom or default templates.
		if( file_exists($custom_file ) ){
			$include_file = $custom_file;
		} else {
			$include_file = PMPRO_COURSES_DIR . "/templates/lessons.php";
		}

		ob_start();
		// Show non-member text if needed.
		$hasaccess = pmpro_has_membership_access(NULL, NULL, true);
		if( is_array( $hasaccess ) ) {
			//returned an array to give us the membership level values
			$post_membership_levels_ids = $hasaccess[1];
			$post_membership_levels_names = $hasaccess[2];
			$hasaccess = $hasaccess[0];
			if ( ! $hasaccess ) {
				echo pmpro_get_no_access_message( '', $post_membership_levels_ids, $post_membership_levels_names );
			}
		}
		
		// lessons template
		include $include_file;
		$after_the_content = ob_get_contents();
		ob_end_clean();

		// Return the content after appending the new post section.
		return $original_content . $after_the_content;
	}
	return $filtered_content;	// Probably false.
}

function pmpro_courses_update_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_update_course' ){
			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$order = intval( $_REQUEST['order'] );
			
			// If no order, set to the max.
			if ( empty( $order ) ) {
				$order = pmproc_get_next_lesson_order( $course );
			}			
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => $course, 'menu_order' => $order ) );
						
			echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			
			wp_die();
		}

	}

}
add_action( 'wp_ajax_pmproc_update_course', 'pmpro_courses_update_course_callback' );

function pmpro_courses_remove_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_remove_course' ){

			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => '' ) );
			
			echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			
			wp_die();
		}
	}

}
add_action( 'wp_ajax_pmproc_remove_course', 'pmpro_courses_remove_course_callback' );

/**
 * Adds columns to the Courses table.
 */
function pmpro_courses_columns($columns) {
    $columns['pmpro_courses_num_lessons'] = __( 'Lesson Count', 'pmpro-courses' );
    $columns['pmpro_courses_level'] = __( 'Level', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_course_posts_columns', 'pmpro_courses_columns' );

function pmpro_courses_columns_content( $column, $course_id ) {
	global $wpdb;
	switch ( $column ) {
		case 'pmpro_courses_num_lessons' :
			$lesson_count = pmpro_courses_get_lesson_count( $course_id );
			printf( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ), number_format_i18n( $lesson_count ) );
			break;
		case 'pmpro_courses_level' :
			$membership_levels = pmpro_getAllLevels( true, true );
			$course_levels = $wpdb->get_col( "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $course_id ) . "'" );
			$level_names = array();
			foreach ( $course_levels as $id ) {
				$level = pmpro_getLevel( $id );
				$level_names[] = $level->name;
			}
			if ( ! empty( $level_names ) ) {
				echo implode( ', ', $level_names );
			} else {
				echo '&#8212;';
			}
			break;
	}
}
add_action( 'manage_pmpro_course_posts_custom_column' , 'pmpro_courses_columns_content', 10, 2 );

/**
 * Get the edit post link for a course.
 */
function pmpro_courses_get_edit_course_link( $course ) {
	if ( ! is_object( $course ) ) {
		$course = get_post( $course );
	}
	
	if ( empty( $course ) ) {
		return false;
	}
	
	return '<a href="' . esc_url( add_query_arg( array( 'post' => $course->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) . '">' . esc_html( $course->post_title ) . '</a>';
}

function pmpro_courses_template_redirect() {
	global $post, $pmpro_pages;

	if( !empty( $post ) ) {		
		// Only check if a PMPro course or lesson.
		if ( $post->post_type != 'pmpro_course' && $post->post_type != 'pmpro_lesson' ) {
			return;
		}
		
		// Let admins in.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Ignore archives and search.
		if ( is_archive() || is_search() ) {
			return;
		}
		
		// Okay check access.
		$access = pmpro_courses_check_level( $post->ID );
		
		// They have access. Let em in.
		if ( $access ) {
			return;
		}
		
		// Make sure we don't redirect away from the levels page if they have odd settings.
		if ( intval( $pmpro_pages['levels'] ) == $post->ID ) {
			return;
		}
		
		// No access.
		if ( $post->post_type == 'pmpro_lesson' ) {
			// Send lessons to their parent unless filtered.
			$redirect_to = apply_filters( 'pmpro_courses_lesson_redirect_to', get_permalink( $post->post_parent ) );
		} else {
			// Don't redirect courses unless a url is passed in filter.
			$redirect_to = apply_filters( 'pmpro_courses_course_redirect_to', null );
		}
		
		if ( $redirect_to ) {
			wp_redirect( $redirect_to );
			exit;
		}	
	}
}
add_action( 'template_redirect', 'pmpro_courses_template_redirect' );

function pmproc_has_course_access( $hasaccess, $mypost, $myuser, $post_membership_levels ) {

	if ( 'pmpro_courses' == $mypost->post_type ) {
		$hasaccess = pmpro_courses_check_level( $mypost->ID );
	}

	return $hasaccess;
}
add_filter( 'pmpro_has_membership_access_filter', 'pmproc_has_course_access', 99, 4 );

function pmproc_course_links_my_account() {
	global $pmpro_pages;
	if ( isset( $pmpro_pages['pmpro_my_courses'] ) ) {
		?>
		<li>
			<a href="<?php the_permalink( $pmpro_pages['pmpro_my_courses'] ); ?>"><?php _e('My Courses', 'pmpro-courses'); ?></a>
		</li>
		<?php
	}
}
add_action( 'pmpro_member_links_bottom', 'pmproc_course_links_my_account' );