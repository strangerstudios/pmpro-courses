<?php 
/**
 * Get a user's progress for a course.
 */
function pmpro_courses_get_user_progress( $course_id, $user_id = null ) {
	global $current_user;
	
	// default to current user
	if ( empty( $user_id ) ) {
		$user_id = $current_user->ID;
	}
	
	if ( empty( $user_id ) ) {
		return array();
	}
		
	$course_progress = get_user_meta( $user_id, 'pmproc_progress_' . $course_id, true );
	
	if ( empty( $course_progress ) ) {
		$course_progress = array();
	}
	
	return $course_progress;
}

/**
 * Mark a lesson as incomplete.
 */
function pmpro_courses_toggle_lesson_progress( $lesson_id, $user_id = null, $complete = null ) {
	global $current_user;
	
	// default to current user
	if ( empty( $user_id ) ) {
		$user_id = $current_user->ID;
	}
	
	if ( empty( $user_id ) ) {
		// no user
		return false;
	}
	
	$lesson = get_post( $lesson_id );
	
	if ( empty( $lesson ) || empty( $lesson->post_parent ) ) {
		// no course to mark against
		return false;
	}
	
	$progress = pmpro_courses_get_user_progress( $lesson->post_parent, $user_id );
	
	if ( isset( $complete ) ) {
		// status was passed in as a parameter
		$progress[$lesson_id] = $complete;
	} else {
		// toggle the status
		if ( $empty( $progress ) || empty( $progress[$lesson_id] ) ) {
			$progress[$lesson_id] = true;
		} else {
			$progress[$lesson_id] = false;
		}
	}
	
	update_user_meta( $user_id, 'pmproc_progress_' . $lesson->post_parent, $progress );
	
	return true;
}

/**
 * Mark a lesson as complete.
 */
function pmpro_courses_mark_lesson_complete( $lesson_id, $user_id = null ) {
	return pmpro_courses_toggle_lesson_progress( $lesson_id, $user_id, true );
}

/**
 * Mark a lesson as incomplete.
 */
function pmpro_courses_mark_lesson_incomplete( $lesson_id, $user_id = null ) {
	return pmpro_courses_toggle_lesson_progress( $lesson_id, $user_id, false );
}

/**
 * Get the current user's completition status for a lesson.
 *
 */
function pmpro_courses_get_user_lesson_status( $lid, $cid, $user_id = null ) {
	// If no user_id passed, try to get the ID for the current user.
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// If no user_id still, just return.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Check whether the user has completed this lesson.
	$progress = pmpro_courses_get_user_progress( $cid, $user_id );
	if ( empty( $progress ) || empty( $progress[$lid] ) ) {
		return 'incomplete';
	} else {
		return 'complete';
	}
}

/**
 * Display a button that allows a user to mark a lesson as complete.
 */
function pmpro_courses_complete_button( $lid, $cid, $user_id = null ) {	
	// Filter to hide the completion button.
	$show_complete_button = apply_filters( 'pmpro_courses_button_show_complete', true );
	if ( empty( $show_complete_button ) ) {
		return;
	}

	// If no user_id passed, try to get the ID for the current user.
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// If no user_id still, just return.
	if ( empty( $user_id ) ) {
		return;
	}

	// Get the user's status for this lesson.
	$lesson_status = pmpro_courses_get_user_lesson_status( $lid, $cid, $user_id );

	if ( empty( $lesson_status ) ) {
		return;
	}

	if ( $lesson_status === 'complete' ) {
		// Filter the text shown as the button title for a completed lesson.
		$lesson_complete_text = apply_filters( 'pmpro_courses_lesson_complete_text', __('Lesson Completed', 'pmpro-courses' ) );
		$content = '<button class="pmpro_courses-button pmpro_courses-button-complete" title="' .  esc_html( $lesson_complete_text ) . '"><span class="dashicons dashicons-yes"></span></button>';
	} else {
		// Filter the text shown as the button title to mark a lesson complete.
		$lesson_to_complete_text = apply_filters( 'pmpro_courses_lesson_to_complete_text', __('Mark Lesson as Complete', 'pmpro-courses') );
		$content = '<button class="pmpro_courses-button pmpro_courses-button-incomplete pmpro_courses-mark-complete-action" lid="' . $lid . '" cid="' . $cid . '" title="' . esc_html( $lesson_to_complete_text ) . '"><span class="dashicons dashicons-yes"></span></button>';
	}

	return $content;
}

/**
 * Get the user's course progress expressed as a percentage of lessons completed.
 */
function pmpro_courses_get_user_progress_percentage( $course_id ) {
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
 * TODO: ...
 */
function pmproc_display_progress_bar( $course_id ){
	$percentage = pmproc_get_user_progress( $course_id );
	if ( $percentage !== 0 ) {
		//return '<div><div data-preset="line" class="ldBar" data-value="' . $percentage . '" style="width: 	100%;"></div></div>';
	} 
	return;
}

/**
 * AJAX callback to toggle completion for a lesson.
 */
function pmpro_courses_record_progress_ajax(){

	if( !empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'pmproc_record_progress' ){

		$user = wp_get_current_user();
		$course_id = intval( $_REQUEST['cid'] );
		$lesson_id = intval( $_REQUEST['lid'] );

		if( $user ){

			$user_id = $user->ID;

			$current_progress = get_user_meta( $user_id, 'pmproc_progress_'.$course_id, true );

			if( empty( $current_progress ) || empty( $current_progress[$lesson_id] ) ) {
				// complete this lesson
				$current_progress[$lesson_id] = true;				
			} else {
				// marking incomplete
				$current_progress[$lesson_id] = false;
			}
			
			update_user_meta( $user_id, 'pmproc_progress_'.$course_id, $current_progress );

			$next_lesson = pmproc_get_next_lesson( $lesson_id, $course_id );

			echo json_encode( array( 'progress' => $current_progress, 'next_lesson' => $next_lesson ) );

			wp_die();

		}		

	}

}
add_action( 'wp_ajax_pmproc_record_progress', 'pmpro_courses_record_progress_ajax' );