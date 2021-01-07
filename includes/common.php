<?php
/**
 * Get an array of lessons data assigned to this course ID.
 *
 */
function pmpro_courses_get_lessons( $course = 0 ) {
	global $wpdb;
	$sql = "SELECT * FROM $wpdb->posts ";
	if ( $course !== 0 ) {
		$sql .= " LEFT JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id 
		WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->postmeta.meta_key = 'pmproc_parent' AND $wpdb->postmeta.meta_value = '$course' ";
	} else {
		$sql .= "WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish' ";
	}
	$results = $wpdb->get_results( $sql );

	// Build the array of $lessons.
	$lessons = array();
	if ( ! empty( $results ) ) {
		foreach( $results as $result ) {
			$lessons[] = array(
				'id' 	=> $result->ID,
				'title' => $result->post_title,
				'content' => $result->post_content,
				'excerpt' => $result->post_excerpt,
				'permalink' => get_the_permalink( $result->ID )
			);
		}
	}
	return $lessons;
}

/**
 * Get a link to the course assigned to this lesson ID.
 *
 */
function pmpro_courses_get_course( $lesson_id ) {
	$parent = intval( get_post_meta( $lesson_id, 'pmproc_parent', true ) );
	if ( $parent ) {
		$course = get_post( $parent );
		if ( $course ) {
			return '<a href="' . esc_url( add_query_arg( array( 'post' => $parent, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) . '">' . esc_html( $course->post_title ) . '</a>';
		}
	}
	return '&#8212;';
}

function pmpro_courses_build_lesson_html( $array_content ){

	$ret = "";

	if( !empty( $array_content ) ){
		
		$count = 1;

		foreach ( $array_content as $lesson ) {

			$ret .= "<tr>";
			$ret .= "<td>".$count."</td>";
			$ret .= "<td><a href='".admin_url( 'post.php?post='.$lesson['id'].'&action=edit' )."' title='".__('Edit', 'pmpro-courses').' '.$lesson['title']."' target='_BLANK'>".$lesson['title']."</a></td>";
			$ret .= "<td>";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_editPost(".$lesson['id']."); void(0);'>".__( 'edit', 'pmpro-courses' )."</a>";
			$ret .= " ";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_removePost(".$lesson['id']."); void(0);'>".__( 'remove', 'pmpro-courses' )."</a>";
			$ret .= "</td>";
			$ret .= "</tr>";

			$count++;
		}

	} 

	return $ret;

}

/**
 * Get a count of lessons assigned to this course ID.
 *
 */
function pmpro_courses_get_lesson_count( $course_id ) {
	global $wpdb;
	$sql = "SELECT count(*) FROM $wpdb->posts ";
	$sql .= " LEFT JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id 
		WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->postmeta.meta_key = 'pmproc_parent' AND $wpdb->postmeta.meta_value = '$course_id' ";
	$results = $wpdb->get_var( $sql );
	return $results;
}

function pmpro_courses_check_level( $post_id ){

	global $wpdb;

	if( is_singular( array( 'pmpro_lesson' ) ) ){

		$parent = intval( get_post_meta( $post_id, 'pmproc_parent', true ) );

		$overrides = get_post_meta( $post_id, 'pmproc_lesson_override', true );

		//We're overriding the membership level in the lesson
		if( $overrides !== '' && $overrides !== null ){
			if(  !pmpro_hasMembershipLevel( $overrides ) ){
				return false;
			} else {
				return true;
			}
		} 

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

/**
 * Display a button that allows a user to mark a course as complete.
 *
 */
function pmproc_complete_button( $lid, $cid ) {
	$button_text = apply_filters( 'pmproc_button_to_complete_text', __('Mark As Complete', 'pmpro-courses') );
	$complete_text = apply_filters( 'pmproc_button_complete_text', __('Completed', 'pmpro-courses' ) );

	$content = '<button class="pmproc_button_mark_complete_action" lid="' . $lid . '" cid="' . $cid . '">' . esc_html( $button_text ) . '</button>';

	$user = wp_get_current_user();
	if ( ! empty( $user ) && ! empty( $user->ID ) ) {
		$user_id = $user->ID;
		$progress = get_user_meta( $user->ID, 'pmproc_progress_' . $cid, true );
		if ( ! empty( $progress ) ) {
			if ( in_array( $lid, $progress ) ) {
				$show_complete_button = apply_filters( 'pmproc_button_show_complete', true );
				if ( $show_complete_button ) {
					$content = '<button class="pmproc_button_mark_complete">'. esc_html( $complete_text ) . '</button>';
				}
			}
		}
	}

	return $content;
}

/**
 * Get the permalink for the next lesson in the course.
 *
 */
function pmproc_get_next_lesson( $lid, $cid ) {
	$args = array(
		'post_type' => 'pmpro_lesson', 
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'pmproc_parent',
				'value' => $cid,
				'compare' => '='
			)
		)
	);
	$the_query = new WP_Query( $args );

	$lessons = array();
	if ( $the_query->have_posts() ){
		while( $the_query->have_posts() ){
			$the_query->the_post();
			$lessons[] = get_the_ID();
		}
	}

	wp_reset_query();

	$searched = array_search( $lid, $lessons );

	$next_key = $searched + 1;

	if ( isset( $lessons[$searched] ) ) {
		return get_the_permalink( $lessons[$next_key] );
	} else {
		return get_the_permalink( $lessons[$searched] );
	}
}

/**
 * Get the user's course progress expressed as a percentage of lessons completed.
 *
 */
function pmproc_get_user_progress( $course_id ) {
	$percentage = 0;
	$lesson_count = pmpro_courses_get_lesson_count( $course_id );
	$progress = count( pmproc_get_complete_lessons( $course_id ) );
	if ( $lesson_count !== 0 && $progress !== 0 ) {
		$percentage = $progress / $lesson_count * 100;
	}
	return $percentage;
}

/**
 * Display a progress bar for the current user and course ID.
 *
 */
function pmproc_display_progress_bar( $course_id ){
	$percentage = pmproc_get_user_progress( $course_id );
	if ( $percentage !== 0 ) {
		return '<div><div data-preset="line" class="ldBar" data-value="' . $percentage . '" style="width: 	100%;"></div></div>';
	} 
	return;
}

function pmproc_get_complete_lessons( $cid ){

	$user = wp_get_current_user();

	$progress = array();

	if( $user ){

		$user_id = $user->ID;

		$progress = get_user_meta( $user_id, 'pmproc_progress_'.$cid, true );	

	}

	if( is_array( $progress ) ){
		return array_unique( $progress );
	} else {
		return array();
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