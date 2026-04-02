<?php
/**
 * Batch enrollment for retroactive course enrollment.
 *
 * When a course is published with PMPro level associations, this queues
 * background tasks via Action Scheduler to enroll all existing active
 * members into the course. Only newly added level associations trigger
 * enrollment; already-processed levels are tracked per-course.
 *
 * This is NOT used by the default module (no enrollment concept there).
 */

defined( 'ABSPATH' ) || exit;

class PMPro_Courses_Batch_Enrollment {

	/**
	 * Number of users to process per batch.
	 */
	const BATCH_SIZE = 50;

	/**
	 * Action Scheduler group name.
	 */
	const AS_GROUP = 'pmpro_courses_enrollment';

	/**
	 * Action Scheduler hook name for batch tasks.
	 */
	const AS_HOOK = 'pmpro_courses_retroactive_enroll_batch';

	/**
	 * Post meta key used to track which level IDs have already had
	 * retroactive enrollment queued for a given course.
	 */
	const PROCESSED_LEVELS_META = '_pmpro_courses_batch_enrollment_levels';

	/**
	 * Register the Action Scheduler callback.
	 */
	public static function init() {
		add_action( self::AS_HOOK, array( __CLASS__, 'process_batch_task' ) );
	}

	/**
	 * Called from each LMS module's save_post handler.
	 *
	 * Detects newly added PMPro level associations for a published course
	 * and schedules batch enrollment for existing members.
	 *
	 * @param int    $post_id     Course post ID.
	 * @param string $post_type   Expected course post type for the active module.
	 * @param string $module_slug Module slug (e.g. 'learndash', 'lifterlms').
	 */
	public static function maybe_schedule_for_course( $post_id, $post_type, $module_slug ) {
		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $post_type || $post->post_status !== 'publish' ) {
			return;
		}

		// Get the level IDs currently associated with this course.
		$current_levels = self::get_level_ids_for_post( $post_id );
		if ( empty( $current_levels ) ) {
			return;
		}

		// Get level IDs we have already processed for retroactive enrollment.
		$processed_levels = get_post_meta( $post_id, self::PROCESSED_LEVELS_META, true );
		if ( ! is_array( $processed_levels ) ) {
			$processed_levels = array();
		}

		// Only act on newly associated levels.
		$new_levels = array_values( array_diff( $current_levels, $processed_levels ) );
		if ( empty( $new_levels ) ) {
			return;
		}

		self::schedule( $post_id, $new_levels, $module_slug );

		// Mark all current levels as processed so future saves don't re-queue.
		update_post_meta( $post_id, self::PROCESSED_LEVELS_META, $current_levels );
	}

	/**
	 * Schedule the first batch task for a course.
	 *
	 * Falls back to synchronous processing if Action Scheduler is not available.
	 *
	 * @param int    $course_id   Course post ID.
	 * @param array  $level_ids   Level IDs to enroll members from.
	 * @param string $module_slug Module slug.
	 */
	public static function schedule( $course_id, $level_ids, $module_slug ) {
		if ( empty( $level_ids ) || empty( $course_id ) ) {
			return;
		}

		if ( ! class_exists( 'PMPro_Action_Scheduler' ) || ! function_exists( 'as_enqueue_async_action' ) ) {
			// Fallback: run synchronously (small sites, AS not available).
			self::process_batch( $course_id, $level_ids, $module_slug, 0 );
			return;
		}

		PMPro_Action_Scheduler::instance()->maybe_add_task(
			self::AS_HOOK,
			array(
				array(
					'course_id'   => $course_id,
					'level_ids'   => $level_ids,
					'module_slug' => $module_slug,
					'offset'      => 0,
				),
			),
			self::AS_GROUP,
			null,
			true // run_asap — enqueue for immediate async execution.
		);
	}

	/**
	 * Action Scheduler callback — unwraps task data and runs one batch.
	 *
	 * @param array $data Task data array with keys: course_id, level_ids, module_slug, offset.
	 */
	public static function process_batch_task( $data ) {
		if ( empty( $data['course_id'] ) || empty( $data['level_ids'] ) || empty( $data['module_slug'] ) ) {
			return;
		}

		self::process_batch(
			(int) $data['course_id'],
			(array) $data['level_ids'],
			(string) $data['module_slug'],
			(int) $data['offset']
		);
	}

	/**
	 * Process one batch of enrollments.
	 *
	 * Fires `pmpro_courses_{module_slug}_retroactive_enroll_user` for each
	 * user in the batch. If the batch is full, chains the next batch as a
	 * new async task.
	 *
	 * @param int    $course_id
	 * @param array  $level_ids
	 * @param string $module_slug
	 * @param int    $offset
	 */
	public static function process_batch( $course_id, $level_ids, $module_slug, $offset ) {
		$user_ids = self::get_active_members( $level_ids, self::BATCH_SIZE, $offset );

		if ( empty( $user_ids ) ) {
			return;
		}

		foreach ( $user_ids as $user_id ) {
			/**
			 * Fires to enroll a single user in a course retroactively.
			 *
			 * Each LMS module hooks into this to perform its own enrollment call.
			 * The hook name is: pmpro_courses_{module_slug}_retroactive_enroll_user
			 *
			 * @param int $user_id   The user to enroll.
			 * @param int $course_id The course to enroll them in.
			 */
			do_action( "pmpro_courses_{$module_slug}_retroactive_enroll_user", (int) $user_id, (int) $course_id );
		}

		// If the batch was full there may be more users — chain the next batch.
		if ( count( $user_ids ) >= self::BATCH_SIZE && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				self::AS_HOOK,
				array(
					array(
						'course_id'   => $course_id,
						'level_ids'   => $level_ids,
						'module_slug' => $module_slug,
						'offset'      => $offset + self::BATCH_SIZE,
					),
				),
				self::AS_GROUP
			);
		}
	}

	/**
	 * Get the PMPro membership level IDs associated with a post.
	 *
	 * @param int $post_id
	 * @return array Level IDs (integers as strings from DB).
	 */
	public static function get_level_ids_for_post( $post_id ) {
		global $wpdb;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = %d",
				$post_id
			)
		);
	}

	/**
	 * Get active member user IDs for a set of level IDs, paginated.
	 *
	 * @param array $level_ids
	 * @param int   $limit
	 * @param int   $offset
	 * @return array User IDs.
	 */
	private static function get_active_members( $level_ids, $limit, $offset ) {
		global $wpdb;

		if ( empty( $level_ids ) ) {
			return array();
		}

		$level_ids    = array_map( 'intval', $level_ids );
		sort( $level_ids );
		$placeholders = implode( ', ', array_fill( 0, count( $level_ids ), '%d' ) );
		$args         = array_merge( $level_ids, array( $limit, $offset ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT DISTINCT user_id
		        FROM {$wpdb->pmpro_memberships_users}
		        WHERE membership_id IN ($placeholders)
		        AND status = 'active'
		        ORDER BY user_id
		        LIMIT %d OFFSET %d";

		return $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
	}
}

PMPro_Courses_Batch_Enrollment::init();
