<?php 
/// Convert this to a class.

/// Keep this here for now.
class PMPro_Courses_User_Progress {

	/**
	 * Create the table for progress tracking.
	 *
	 * @since TBD
	 * @return void
	 */
	public static function create_progress_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
		$sql = "CREATE TABLE {$table_name} (
		user_id bigint(20) unsigned NOT NULL,
		lesson_id bigint(20) unsigned NOT NULL,
		completed_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (user_id, lesson_id),
		KEY idx_lesson_id (lesson_id),
		KEY idx_completed_at (completed_at))";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Toggle progress for lesson completion (complete or reset/clear)
	 * 
	 * @since TBD
	 * @return bool $result Returns the result.
	 */
	public static function toggle_lesson_progress( $lesson_id, $user_id = null, $complete = null ) {
		global $wpdb;

		// default to current user
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
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

		// Mark the lesson as either completed or remove it.
		$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
		if ( $complete ) {
			// Mark lesson as complete
			$wpdb->insert( $table_name, array(
				'user_id' => $user_id,
				'lesson_id' => $lesson_id,
				'completed_at' => current_time( 'mysql' )
			), array( '%d', '%d', '%s' ) );
		} else {
			// Mark lesson as incomplete
			$wpdb->delete( $table_name, array(
				'user_id' => $user_id,
				'lesson_id' => $lesson_id
			), array( '%d', '%d' ) );
		}

		return true;
	}

	/**
	 * Get the users progress status for a particular lesson.
	 *
	 * @param int $lesson_id The lesson ID we are checking for.
	 * @param int $user_id The learners user ID.
	 * @return bool $completed Returns true if lesson is marked as completed or false if not.
	 */
	public static function get_user_lesson_status( $lesson_id, $user_id = null ) {
		global $wpdb;

		// default to current user if no user ID is passed in.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Still no user ID, let's bail.
		if ( empty( $user_id ) ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
		$completed = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) FROM {$table_name}
			WHERE user_id = %d AND lesson_id = %d
		", $user_id, $lesson_id ) );

		return (bool) $completed;
	}

	/**
	 * Get the user's progress for a specific course.
	 * @since TBD
	 */
	public static function get_course_progress_for_user( $course_id, $user_id = null ) {
		global $wpdb;

		// default to current user if no user ID is passed in.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Still no user ID, let's bail.
		if ( empty( $user_id ) ) {
			return 0;
		}

		// Get all lessons for this course.
		$lessons = get_posts( array(
			'post_type'      => 'pmpro_lesson',
			'post_parent'    => $course_id,
			'posts_per_page' => 250,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		) );

		if ( empty( $lessons ) ) {
			return 0;
		}

		$lesson_ids = implode( ',', array_map( 'intval', $lessons ) );

		$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
		$completed_count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) FROM {$table_name}
			WHERE user_id = %d AND lesson_id IN ({$lesson_ids})
		", $user_id ) );

		if ( $completed_count === null ) {
			return 0;
		}

		return ( $completed_count / count( $lessons) ) * 100;
	}

	///
	public static function get_completed_lessons_for_a_course( $course_id, $user_id = '' ) {
		global $wpdb;

		// default to current user if no user ID is passed in.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Still no user ID, let's bail.
		if ( empty( $user_id ) ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
		$completed_lessons = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$table_name}
			WHERE user_id = %d AND lesson_id IN (
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'pmpro_lesson' AND post_parent = %d
			)
			ORDER BY completed_at ASC
		", $user_id, $course_id ) );

		return $completed_lessons;
	}
} // End of class.

function pmpro_courses_get_user_lesson_status(){
	return true;
}
/// This should rather be in another file I think.
/**
 * Display a button that allows a user to mark a lesson as complete or show completed if the lesson is completed.
 */
function pmpro_courses_complete_lesson_button( $lid, $user_id = null ) {
	// If no user_id passed, try to get the ID for the current user.
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// If no user_id still, just return.
	if ( empty( $user_id ) ) {
		return;
	}

	// Has the lesson been completed, return true or false.
	$is_completed = isset( $_REQUEST['complete'] ) ? (int) $_REQUEST['complete'] : PMPro_Courses_User_Progress::get_user_lesson_status( $lid, $user_id );

	// Get the user's status for this lesson.
	if ( $is_completed ) {
		$content = '<p><input class="pmpro_courses_lesson_toggle" id="pmpro_courses_lesson' . esc_attr( $lid ) . '_toggle" data-lid="' . esc_attr( $lid ) . '" type="checkbox" checked="checked" /> <label for="pmpro_courses_lesson' . esc_attr( $lid ) . '_toggle">' . esc_html__( 'Completed', 'pmpro-courses' ) . '</label></p>';
	} else {		
		$content = '<p><input class="pmpro_courses_lesson_toggle" id="pmpro_courses_lesson' . esc_attr( $lid ) . '_toggle" data-lid="' . esc_attr( $lid ) . '" type="checkbox" /> <label for="pmpro_courses_lesson' . esc_attr( $lid ) . '_toggle">' . esc_html__( 'Mark Complete', 'pmpro-courses' ) . '</label></p>';
	}

	return $content;
}

/**
 * AJAX callback to toggle completion for a lesson.
 */
function pmpro_courses_toggle_lesson_progress_ajax(){		
	$user_id = get_current_user_id();
			
	$lesson_id = intval( $_REQUEST['lid'] );
	$complete  = isset($_REQUEST['complete']) ? (bool) intval($_REQUEST['complete']) : null;

	if ( ! empty( $user_id ) ) {
		PMPro_Courses_User_Progress::toggle_lesson_progress( $lesson_id, $user_id, $complete );		
		echo pmpro_courses_complete_lesson_button( $lesson_id, $user_id );
				
		wp_die();
	}
}
add_action( 'wp_ajax_pmpro_courses_toggle_lesson_progress', 'pmpro_courses_toggle_lesson_progress_ajax' );