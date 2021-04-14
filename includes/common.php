<?php
/**
 * Get an array of all PMPro Courses modules.
 * Use the pmpro_courses_modules filter to add your own modules.
 */
function pmpro_courses_get_modules() {
	$modules = array(
		array(
			'name' => __( 'Default', 'pmpro-courses' ),
			'slug' => 'default',
			'description' => __( 'The Course and Lesson post types bundled with PMPro Courses.', 'pmpro-courses' ),
		)
	);
	$modules = apply_filters( 'pmpro_courses_modules', $modules );

	return $modules;
}

/**
 * Check if a specific module is active.
 */
function pmpro_courses_is_module_active( $module ) {
	$active_modules = get_option( 'pmpro_courses_modules', array() );
	if ( in_array( $module, $active_modules ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Get an array of lessons data assigned to this course ID.
 */
function pmpro_courses_get_lessons( $course ) {
	// Get ID from course if an object is passed in.
	if ( is_object( $course ) ) {
		$course = $course->ID;
	}

	// Return false if no course ID was passed in.
	if ( empty( $course ) ) {
		return false;
	}
	
	// Set up args for query.
	$args = array(
		'post_parent' => $course,
		'numberposts' => -1,
		'post_type' => 'pmpro_lesson',
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);
	
	// Return lessons.
	return get_posts( $args );
}

/**
 * Get the next order # for a lesson in a course.
 */
function pmproc_get_next_lesson_order( $course ) {
	// In case a full post object is passed in.
	if ( is_object( $course ) ) {
		$course = $course->ID;
	}
	
	// Get all the lessons.
	$lessons = pmpro_courses_get_lessons( $course );
		
	if ( empty( $lessons ) ) {
		// Default to 1
		return 1;
	} else {
		// Last menu_order + 1
		$last_child = end( $lessons );
		return $last_child->menu_order + 1;
	}
}

function pmpro_courses_build_lesson_html( $lessons ){

	$ret = "";

	if( !empty( $lessons ) ){
		
		$count = 1;

		foreach ( $lessons as $lesson ) {

			$ret .= "<tr>";
			$ret .= "<td>".$lesson->menu_order."</td>";
			$ret .= "<td><a href='".admin_url( 'post.php?post=' . $lesson->ID.'&action=edit' ) . "' title='" . __('Edit', 'pmpro-courses') .' '. $lesson->post_title. "' target='_BLANK'>". $lesson->post_title ."</a></td>";
			$ret .= "<td>";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_editPost(".$lesson->ID.",".$lesson->menu_order."); void(0);'>".__( 'edit', 'pmpro-courses' )."</a>";
			$ret .= " ";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_removePost(".$lesson->ID."); void(0);'>".__( 'remove', 'pmpro-courses' )."</a>";
			$ret .= "</td>";
			$ret .= "</tr>";

			$count++;
		}

	} 

	return $ret;

}

/**
 * Get a count of lessons assigned to this course ID.
 */
function pmpro_courses_get_lesson_count( $course_id ) {
	global $wpdb;

	$sql = "SELECT count(*) FROM $wpdb->posts ";
	$sql .= " WHERE post_parent = '$course_id' AND post_type = 'pmpro_lesson'";
	$results = $wpdb->get_var( $sql );
	return intval( $results );
}

function pmpro_courses_check_level( $post_id ){

	global $wpdb;

	if( is_singular( array( 'pmpro_lesson' ) ) ){

		$parent = intval( get_post_meta( $post_id, 'pmproc_parent', true ) );

		if( $parent !== '' ){

			$required_membership = array();
			$sql = "SELECT * FROM $wpdb->pmpro_memberships_pages WHERE `page_id` = ".$parent."";
			$results = $wpdb->get_results( $sql  );
			if( !empty( $results ) ){
				foreach( $results as $result ){
					$required_membership[] = intval( $result->membership_id );
				}
				if( !pmpro_hasMembershipLevel( $required_membership ) ){
					return false;
				} else {
					return true;
				}
			}

		}

	}

	if( is_singular( array( 'pmpro_course' ) ) ){
		$required_membership = array();
		$sql = "SELECT * FROM $wpdb->pmpro_memberships_pages WHERE `page_id` = ".$post_id."";
		$results = $wpdb->get_results( $sql  );
		if( !empty( $results ) ){
			foreach( $results as $result ){
				$required_membership[] = intval( $result->membership_id );
			}			
			if( !pmpro_hasMembershipLevel( $required_membership ) ){
				return false;
			} else {
				return true;
			}
		}
	}

}

function pmproc_get_courses( $posts_per_page = 5, $user_id = false ){

	global $wpdb;

	$args = array(
		'post_type' => 'pmpro_course',
		'posts_per_page' => $posts_per_page,
	);

	$the_query = new WP_Query( $args );

	$courses = array();

	if( $the_query->have_posts() ){

		while( $the_query->have_posts() ){

			$the_query->the_post();
			
			$course_id = get_the_ID();

			if( $user_id ){

				$sql = "SELECT * FROM $wpdb->pmpro_memberships_pages WHERE page_id = '$course_id'";

				$results = $wpdb->get_results( $sql );

				if( $results ){

					foreach( $results as $res ){

						$ordsql = "SELECT * FROM $wpdb->pmpro_membership_orders WHERE user_id = '".$user_id."' AND membership_id = '".$res->membership_id."' AND status = 'success'";

						$orders = $wpdb->get_row( $ordsql );

						if( $orders ){
							$courses[$course_id] = apply_filters( 'pmproc_return_courses_array', array(
								'id' => $course_id,
								'title' => get_the_title(),
								'permalink' => get_the_permalink(),
								'featured' => get_the_post_thumbnail_url( $course_id ),
								'excerpt' => get_the_excerpt(),
								'lessons' => pmpro_courses_get_lesson_count( $course_id )
							), $course_id );
						}
					}
				}

			} else {
				$courses[$course_id] = apply_filters( 'pmproc_return_courses_array', array(
					'id' => $course_id,
					'title' => get_the_title(),
					'permalink' => get_the_permalink(),
					'featured' => get_the_post_thumbnail_url( $course_id ),
					'excerpt' => get_the_excerpt(),
					'lessons' => pmpro_courses_get_lesson_count( $course_id )
				), $course_id );
			}
		}
	}

	wp_reset_query();

	return $courses;

}