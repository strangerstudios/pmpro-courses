<?php 
class PMPro_Courses_User_Progress {
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

		return round( ( $completed_count / count( $lessons ) ) * 100 );
	}

	/**
	 * Get all completed lessons for a particular course (and optionally a specific user).
	 *
	 * @param int $course_id The course (post->ID) we want to query.
	 * @param int $user_id The WordPress user ID we want to query. Optional.
	 * @return array $completed_lessons A list of completed lesson IDs with additional data around the release.
	 */
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

	/**
	 * Get a list of courses for a user that have no progress at all.
	 *
	 * @since TBD
	 *
	 * @param int|null $user_id
	 * @return array|false Array of WP_Post objects (pmpro_course) or false if no user.
	 */
	public static function pmpro_courses_get_no_activity_courses_for_user( $user_id = null ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( empty( $user_id ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';

		// Find courses that (a) have lessons, and (b) have NO progress rows for this user.
		$sql = $wpdb->prepare(
			"
			SELECT c.*
			FROM {$wpdb->posts} AS c
			WHERE c.post_type = 'pmpro_course'
			AND c.post_status IN ('publish','private')
			AND EXISTS (
					SELECT 1
					FROM {$wpdb->posts} AS l
					WHERE l.post_type = 'pmpro_lesson'
					AND l.post_parent = c.ID
					AND l.post_status IN ('publish','private')
			)
			AND NOT EXISTS (
					SELECT 1
					FROM {$table} AS p
					INNER JOIN {$wpdb->posts} AS l2
							ON l2.ID = p.lesson_id
						AND l2.post_type = 'pmpro_lesson'
						AND l2.post_parent = c.ID
						AND l2.post_status IN ('publish','private')
					WHERE p.user_id = %d
			)
			ORDER BY c.post_title ASC
			",
			$user_id
		);

		$courses = $wpdb->get_results( $sql );
		return $courses;
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

/**
 * Retroactively migrate any user progress from user meta to the new progress table.
 * 
 * @since TBD
 */
function pmpro_courses_migrate_course_progress() {
	global $wpdb;

	// User is logged out, let's bail.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Only run the code if we're viewing a lesson or course.
	if ( ! is_singular( 'pmpro_course' ) && ! is_singular( 'pmpro_lesson' ) ) {
		return;
	}

	// Get the course ID.
	if ( is_singular( 'pmpro_course' ) ) {
		$course_id = get_the_ID();
	} elseif ( is_singular( 'pmpro_lesson' ) ) {
		$lesson = get_post();
		if ( empty( $lesson ) || empty( $lesson->post_parent ) ) {
			return;
		}
		$course_id = $lesson->post_parent;
	}

	// Try to get the meta to see if there's any "old" progress to migrate.
	$previous_progress = get_user_meta( get_current_user_id(), 'pmpro_courses_progress_' . $course_id , true );

	$migrated = false;
	if ( is_array( $previous_progress ) && ! empty( $previous_progress ) ) {
		foreach ( $previous_progress as $lesson_id => $lesson_status ) {
			// Migrate each lesson to the new table.
			$result = PMPro_Courses_User_Progress::toggle_lesson_progress( $lesson_id, get_current_user_id(), true );
			if ( $result ) {
				$migrated = true;
			}
		}

		// Only delete the old user meta if all rows were inserted successfully.
		if ( $migrated ) {
			delete_user_meta( get_current_user_id(), 'pmpro_courses_progress_' . $course_id );
		}
	}
}
add_action( 'wp', 'pmpro_courses_migrate_course_progress' );