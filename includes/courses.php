<?php 
/**
 * Content filter to show additional course information on the single course page.
 * Hooked into `pmpro_membership_content_filter` in modules/default.php.
 */
function pmpro_courses_show_course_content_and_lessons( $filtered_content, $original_content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {		
		// Show non-member text if needed.
		$no_access_message = '';
		$hasaccess = pmpro_has_membership_access(NULL, NULL, true);
		if( is_array( $hasaccess ) ) {
			//returned an array to give us the membership level values
			$post_membership_levels_ids = $hasaccess[1];
			$post_membership_levels_names = $hasaccess[2];
			$hasaccess = $hasaccess[0];
			if ( ! $hasaccess ) {
				$no_access_message = pmpro_get_no_access_message( '', $post_membership_levels_ids, $post_membership_levels_names );

				// If there's only one level, let's replace the levels URL with the checkout URL.
				if ( count( $post_membership_levels_ids ) == 1 ) {
					$checkout_link = pmpro_url( 'checkout', '?level=' . esc_attr( $post_membership_levels_ids[0] ) );
					$levels_url = pmpro_url( 'levels' );
					$no_access_message = str_replace( $levels_url, $checkout_link, $no_access_message );
				}
			}
		}

		if ( $hasaccess || pmpro_courses_show_course_content_to_nonmembers() ) {
			return $original_content . $no_access_message;
		}
	}
	return $filtered_content;	// Probably false.
}

/**
 * Content filter to show lessons at the end of a PMPro course single post.
 */
function pmpro_courses_add_lessons_to_course( $content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {
		$content .= pmpro_courses_get_lessons_html( $post->ID );
	}
	return $content;
}

/**
 * Should we override PMPro to show the full course content to non-members?
 *
 * By default, PMPro Courses will override PMPro to show the full course
 * content, even to non-members. Set this filter to false to change this.
 * If this filter is set to false, the PMPro excerpt setting will be honored.
 */
function pmpro_courses_show_course_content_to_nonmembers() {
	$show = apply_filters( 'pmpro_courses_show_course_content_to_nonmembers', true );
	return $show;
}

/**
 * AJAX callback to add/edit a lesson to a course from the edit course page.
 */
function pmpro_courses_update_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmpro_courses_update_course' ){

			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pmpro_courses_admin_nonce' ) ) {
				wp_die( __( 'Nonce is invalid', 'pmpro-courses' ) );
			}
			
			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$order = intval( $_REQUEST['order'] );
			
			// If no order, set to the max.
			if ( empty( $order ) ) {
				$order = pmpro_courses_get_next_lesson_order( $course );
			}			
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => $course, 'menu_order' => $order ) );
						
			echo pmpro_courses_get_lessons_table_html( pmpro_courses_get_lessons( $course ) );
			
			wp_die();
		}

	}

}
add_action( 'wp_ajax_pmpro_courses_update_course', 'pmpro_courses_update_course_callback' );

/**
 * AJAX callback to remove a lesson from a course on the edit course page.
 */
function pmpro_courses_remove_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmpro_courses_remove_course' ){

			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			// check if nonce is valid
			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pmpro_courses_admin_nonce' ) ) {
				wp_die( __( 'Nonce is invalid', 'pmpro-courses' ) );
			}

			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => '' ) );
			
			echo pmpro_courses_get_lessons_table_html( pmpro_courses_get_lessons( $course ) );
			
			wp_die();
		}
	}

}
add_action( 'wp_ajax_pmpro_courses_remove_course', 'pmpro_courses_remove_course_callback' );

/**
 * Adds columns to the Courses table.
 */
function pmpro_courses_columns($columns) {
    $columns['pmpro_courses_num_lessons'] = esc_html__( 'Lesson Count', 'pmpro-courses' );
    if ( function_exists( 'pmpro_getAllLevels' ) ) {
		$columns['pmpro_courses_level'] = esc_html__( 'Level', 'pmpro-courses' );
    }
	return $columns;
}
add_filter( 'manage_pmpro_course_posts_columns', 'pmpro_courses_columns' );

/**
 * Callback for column content on the Courses table.
 */
function pmpro_courses_columns_content( $column, $course_id ) {
	global $wpdb;
	switch ( $column ) {
		case 'pmpro_courses_num_lessons' :
			$lesson_count = pmpro_courses_get_lesson_count( $course_id );
			printf( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ), number_format_i18n( $lesson_count ) );
			break;
		case 'pmpro_courses_level' :
			if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
				break;
			}
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

/**
 * When a non-member tries to access a member lesson,
 * redirect to the parent course page. 
 */
function pmpro_courses_template_redirect() {
	global $post, $pmpro_pages;

	if( !empty( $post ) && is_singular() ) {		
		// Only check if a PMPro course or lesson.
		if ( $post->post_type != 'pmpro_course' && $post->post_type != 'pmpro_lesson' ) {
			return;
		}
		
		// Let admins in.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Okay check access.
		if ( $post->post_type == 'pmpro_course' ) {
			$access = pmpro_has_membership_access( $post->ID );
		} else {
			$access = pmpro_has_membership_access( $post->post_parent );
		}	
		
		// They have access. Let em in.
		if ( $access ) {
			return;
		}
		
		// Make sure we don't redirect away from the levels page if they have odd settings.
		if ( intval( $pmpro_pages['levels'] ) == $post->ID ) {
			return;
		}
		
		// No access.
		if ( $post->post_type == 'pmpro_course' ) {
			// Don't redirect courses unless a url is passed in filter.
			$redirect_to = apply_filters( 'pmpro_courses_course_redirect_to', null );			
		} else {
			// Send lessons to their parent unless filtered.
			$redirect_to = apply_filters( 'pmpro_courses_lesson_redirect_to', get_permalink( $post->post_parent ) );
		}
		
		if ( $redirect_to ) {
			wp_redirect( $redirect_to );
			exit;
		}	
	}
}
add_action( 'template_redirect', 'pmpro_courses_template_redirect' );

/**
 * Hide the prev/next links for courses.
 * Hook in on init and remove_action(...) to disable this.
 * @since .1
 */
function pmpro_courses_hide_adjacent_post_links_for_courses( $output, $format, $link, $adjacent_post, $adjacent ) {
	global $post;
	
	if ( ! empty( $post ) && ! empty( $post->post_type ) && $post->post_type == 'pmpro_course' ) {
		$output = '';
	}
	
	return $output;
}
add_action( 'previous_post_link', 'pmpro_courses_hide_adjacent_post_links_for_courses', 10, 5 );
add_action( 'next_post_link', 'pmpro_courses_hide_adjacent_post_links_for_courses', 10, 5 );

/**
 * Generate a JSON response for AJAX callbacks.
 *
 * @param bool $success Whether the operation was successful.
 * @param string $message The message to return.
 * @return void Despite the void return, this function echoes JSON response for AJAX callback and dies the WP standard way.
 * @since TBD
 */
function pmpro_courses_echo_JSON_response( $success, $message ) {
	echo wp_json_encode(
		array(
			'success' => $success,
			'message' => $message,
		)
	);
	wp_die();
}

/**
 * Update All Course Lessons menu_order attrbute based on the UI drag and drop.
 *
 * @return void Despite the void return, this function echoes JSON response for AJAX callback and dies the WP standard way.
 * @since TBD
 */
function pmpro_courses_update_course_order_callback() {
	//Bail if user can't edit posts
	if ( ! current_user_can( 'edit_posts' ) ) {
		pmpro_courses_echo_JSON_response( false, __( 'User cannot edit posts', 'pmpro-courses' ) );
	}

	//Check nonce
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pmpro_courses_admin_nonce' ) ) {
		pmpro_courses_echo_JSON_response( false, __( 'Nonce is invalid', 'pmpro-courses' ) );
	}

	$course = intval( $_REQUEST['course'] );
	$lessons = $_REQUEST['lessons'];

	// Update the course order
	foreach ( $lessons as $order => $lesson ) {
		wp_update_post( array( 'ID' => $lesson, 'menu_order' => $order + 1, 'post_parent' => $course ) );
	}

	//return success json
	pmpro_courses_echo_JSON_response( true, __( 'Lessons order updated', 'pmpro-courses' ) );
}

add_action( 'wp_ajax_pmpro_courses_update_course_order', 'pmpro_courses_update_course_order_callback' );