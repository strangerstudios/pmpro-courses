<?php
/**
 * Background repair of LMS course enrollments.
 *
 * When the level restrictions for a course change, we queue one Action Scheduler
 * task per affected member. Each task asks the active LMS module to reconcile
 * that member's enrollments against the current level -> course map, so the
 * work is idempotent and self-healing (no state is kept between runs).
 *
 * This mirrors the pattern used in PMPro core for LifterLMS streamline mode.
 * Requires PMPro 3.6+ for the `pmpro_after_updating_post_level_restrictions` hook.
 *
 * This is NOT used by the default module (no enrollment concept there).
 */

defined( 'ABSPATH' ) || exit;

class PMPro_Courses_Batch_Enrollment {

	/**
	 * Action Scheduler group name.
	 */
	const AS_GROUP = 'pmpro_courses_enrollment';

	/**
	 * Action Scheduler hook that fans out one task per user.
	 */
	const AS_HOOK_QUEUE = 'pmpro_courses_repair_all_enrollments_callback';

	/**
	 * Action Scheduler hook that repairs a single user's enrollments.
	 * LMS modules hook their own repair method onto this action.
	 */
	const AS_HOOK_USER = 'pmpro_courses_repair_user_enrollments';

	/**
	 * Number of user IDs to fetch per query while building the queue.
	 */
	const QUEUE_CHUNK_SIZE = 250;

	/**
	 * Register hooks. Runs on plugins_loaded so PMPro core is guaranteed to be loaded.
	 */
	public static function init() {
		if ( ! self::is_available() ) {
			return;
		}

		add_action( 'pmpro_after_updating_post_level_restrictions', array( __CLASS__, 'after_updating_post_level_restrictions' ) );
		add_action( self::AS_HOOK_QUEUE, array( __CLASS__, 'repair_all_enrollments_callback' ) );
	}

	/**
	 * Whether the PMPro Action Scheduler wrapper is available (PMPro 3.6+).
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'PMPro_Action_Scheduler' ) && function_exists( 'as_enqueue_async_action' );
	}

	/**
	 * Course post types for the active LMS modules.
	 *
	 * @return array Post type slugs.
	 */
	public static function get_course_post_types() {
		/**
		 * Filter the post types that should trigger an enrollment repair when
		 * their level restrictions change. LMS modules add their course post type here.
		 *
		 * @param array $post_types Post type slugs.
		 */
		return array_unique( (array) apply_filters( 'pmpro_courses_enrollment_course_post_types', array() ) );
	}

	/**
	 * When the level restrictions for a course change, queue a repair for its members.
	 *
	 * @param int $post_id The post whose level restrictions were updated.
	 */
	public static function after_updating_post_level_restrictions( $post_id ) {
		if ( ! in_array( get_post_type( $post_id ), self::get_course_post_types(), true ) ) {
			return;
		}

		// No module is listening, nothing to do.
		if ( ! has_action( self::AS_HOOK_USER ) ) {
			return;
		}

		self::schedule_repair_for_course( $post_id );
	}

	/**
	 * Queue the fan-out task for a course.
	 *
	 * @param int $course_id Course post ID.
	 */
	public static function schedule_repair_for_course( $course_id ) {
		if ( ! self::is_available() ) {
			return;
		}

		PMPro_Action_Scheduler::instance()->maybe_add_task(
			self::AS_HOOK_QUEUE,
			array( 'course_id' => (int) $course_id ),
			self::AS_GROUP,
			null,
			true
		);
	}

	/**
	 * Action Scheduler callback: queue one repair task per member.
	 *
	 * We queue everyone who has ever held a level (not just members of the course's
	 * current levels) because the hook fires after the change, so a level that was
	 * just removed from the course is no longer visible here and its members would
	 * otherwise never be unenrolled.
	 *
	 * @param int $course_id Course post ID.
	 */
	public static function repair_all_enrollments_callback( $course_id ) {
		global $wpdb;

		// Halt Action Scheduler processing until we finish adding tasks.
		PMPro_Action_Scheduler::instance()->halt();

		$last_user_id = 0;
		do {
			// Keyset pagination on user_id so churn during the loop can't skip or repeat rows.
			$user_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT user_id
					 FROM {$wpdb->pmpro_memberships_users}
					 WHERE user_id > %d
					 ORDER BY user_id
					 LIMIT %d",
					$last_user_id,
					self::QUEUE_CHUNK_SIZE
				)
			);

			foreach ( $user_ids as $user_id ) {
				self::schedule_repair_for_user( $user_id );
				$last_user_id = (int) $user_id;
			}
		} while ( count( $user_ids ) === self::QUEUE_CHUNK_SIZE );

		// Resume Action Scheduler processing.
		PMPro_Action_Scheduler::instance()->resume();
	}

	/**
	 * Queue a repair task for a single user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function schedule_repair_for_user( $user_id ) {
		if ( ! self::is_available() ) {
			return;
		}

		PMPro_Action_Scheduler::instance()->maybe_add_task(
			self::AS_HOOK_USER,
			array( 'user_id' => (int) $user_id ),
			self::AS_GROUP
		);
	}
}
// Priority 20 so this runs after pmpro_courses_setup_modules() has registered the modules.
add_action( 'plugins_loaded', array( 'PMPro_Courses_Batch_Enrollment', 'init' ), 20 );
