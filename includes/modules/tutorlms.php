<?php
class PMPro_Courses_TutorLMS extends PMPro_Courses_Module {

	public $slug = 'tutorlms';

	/**
	 * Initial setup for the TutorLMS module.
	 *
	 * @since 1.0
	 */
	public function init() {
		add_filter( 'pmpro_courses_modules', array( 'PMPro_Courses_TutorLMS', 'add_module' ), 10, 1 );
	}

	/**
	 * Initial setup for the TutorLMS module when active.
	 *
	 * @since 1.0
	 */
	public function init_active() {

		add_action( 'admin_menu', array( 'PMPro_Courses_TutorLMS', 'admin_menu' ), 20 );

		// If TutorLMS is not active, we're done here.
		if ( ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		add_filter( 'pmpro_membership_content_filter', array( 'PMPro_Courses_TutorLMS', 'pmpro_membership_content_filter' ), 10, 2 );
		add_action( 'template_redirect', array( 'PMPro_Courses_TutorLMS', 'template_redirect' ) );

		add_action( 'pmpro_after_all_membership_level_changes', array( 'PMPro_Courses_TutorLMS', 'pmpro_after_all_membership_level_changes' ) );		
	}

	/**
	 * Add TutorLMS to the modules list.
	 */
	public static function add_module( $modules ) {

		$modules[] = array(
			'name'        => esc_html__( 'Tutor LMS', 'pmpro-courses' ),
			'slug'        => 'tutorlms',
			'title'       => esc_html__( 'Integrate with the Tutor LMS plugin for WordPress.', 'pmpro-courses' ),
			'description' => '<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=plugin&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-TutorLMS#tutorlms-module" target="_blank">' . esc_html__( 'Read the Tutor LMS Integration documentation &raquo;', 'pmpro-courses' ) . '</a>',
		);

		return $modules;
	}

	/**
	 * Add Require Membership box to TutorLMS courses.
	 */
	public static function admin_menu() {
		if ( function_exists( 'pmpro_page_meta' ) ) {
			add_meta_box( 'pmpro_page_meta', esc_html__( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'courses', 'side' );
		}
	}

	/**
	 * If a course requires membership, redirect its lessons
	 * to the main course page.
	 */
	public static function template_redirect() {
		global $post, $pmpro_pages;

		// Only check if a TutorLMS CPT.
		if ( ! empty( $post ) && is_singular( array( 'courses', 'topics', 'lesson', 'tutor_quiz' ) ) ) {

			// If lesson check grandparent (course) access
			if ( is_singular( 'lesson' ) ) {
				$topic = get_post( $post->post_parent );
				$post_grand_parent = ! empty( $topic ) ? (int) $topic->post_parent : (int) $post->post_parent;

				$access = self::has_access_to_post( $post_grand_parent );
			} else {
				$access = self::has_access_to_post( $post->ID );
			}			

			// They have access. Let them in.
			if ( $access ) {
				return;
			}

			// Make sure we don't redirect away from the levels page if they have odd settings.
			if ( intval( $pmpro_pages['levels'] ) == $post->ID ) {
				return;
			}
			// No access.
			if ( $post->post_type == 'courses' ) {
				// Don't redirect courses unless a url is passed in filter.
				$redirect_to = apply_filters( 'pmpro_courses_course_redirect_to', null );
			} else {
				// Send lessons and other content to the parent course.
				$course_id = intval( tutor_utils()->get_course_id_by( 'lesson', $post->ID ) );
				if ( ! empty( $course_id ) ) {
					$redirect_to = get_permalink( $course_id );
				} else {
					$redirect_to = null;
				}
				$redirect_to = apply_filters( 'pmpro_courses_lesson_redirect_to', $redirect_to );
			}

			if ( $redirect_to ) {
				wp_redirect( $redirect_to );
				exit;
			}
		}
	}

	/**
	 * Check if a user has access to a TutorLMS course, lesson, etc.
	 * For courses, the default PMPro check works.
	 * For other TutorLMS CPTs, we first find the course_id.
	 * For public courses, access to lessons/etc is
	 * the same as access for the associated course.
	 * For private courses (with assignedments),
	 * access is true to let TutorLMS handle it.
	 */
	public static function has_access_to_post( $post_id = null, $user_id = null ) {

		// Use loop/global post if no $post_id was passed in.
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Fallback on queried object if called out of loop
		if ( ! $post_id ) {
			$post_id = get_queried_object_id();
		}

		// No post, return true.
		if ( ! $post_id ) {
			return true;
		}

		// Let's assume they have access, in case Tutor LMS has enrollment settings etc.
		$access = true;
		$tutor_non_course_cpts = array( 'lesson', 'topic', 'tutor_quiz' );

		// Check if this is other TutorLMS CPT's.
		if ( ! in_array( get_post_type( $post_id ), $tutor_non_course_cpts ) ) {
			// Let PMPro handle these CPTs.
			$access = self::pmpro_has_membership_access( $post_id, $user_id );
		} else {
			// Let admins in.
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}

			$course_id = intval( tutor_utils()->get_course_id_by( 'lesson', $post_id ) );

			// Check course.
			$public_course = get_post_meta( $course_id, '_tutor_is_public_course', true );

			// If the course is public, but requires level let us handle the restriction.
			if ( ! empty( $course_id ) && $public_course == 'yes' ) {
				$access = self::pmpro_has_membership_access( $course_id, $user_id );
			} elseif ( ! empty( $course_id ) ) {
				//Let TutorLMS handle it through enrollment.
				$access = true;
			} else {
				// A TutorLMS CPT with no course. Let PMPro handle it.
				$access = self::pmpro_has_membership_access( $post_id, $user_id );
			}
		}
		return $access;
	}

	/**
	 * Run pmpro_has_membership_access without our hooks active.
	 */
	public static function pmpro_has_membership_access( $post_id = null, $user_id = null ) {
		remove_filter( 'pmpro_has_membership_access_filter', array( 'PMPro_Courses_TutorLMS', 'pmpro_has_membership_access_filter' ), 10, 4 );
		$hasaccess = pmpro_has_membership_access( $post_id, $user_id );
		add_filter( 'pmpro_has_membership_access_filter', array( 'PMPro_Courses_TutorLMS', 'pmpro_has_membership_access_filter' ), 10, 4 );
		return $hasaccess;
	}

	/**
	 * Filter PMPro access so check on lessons and
	 * other TutorLMS post types checks the associated course.
	 */
	public static function pmpro_has_membership_access_filter( $hasaccess, $mypost, $myuser, $post_membership_levels ) {
		// Don't need to check if already restricted.
		if ( ! $hasaccess ) {
			return $hasaccess;
		}

		return self::has_access_to_post( $mypost->ID, $myuser->ID );
	}

	/**
	 * Override PMPro's the_content filter.
	 * We want to show course content even if it requires membership.
	 * Still showing the non-member text at the bottom.
	 */
	public static function pmpro_membership_content_filter( $filtered_content, $original_content ) {

		if ( is_singular( 'courses' ) ) {
			// Show non-member text if needed.
			ob_start();
			// Get hasaccess ourselves so we get level ids and names.
			$hasaccess = pmpro_has_membership_access( null, null, true );
			if ( is_array( $hasaccess ) ) {
				// returned an array to give us the membership level values
				$post_membership_levels_ids   = $hasaccess[1];
				$post_membership_levels_names = $hasaccess[2];
				$hasaccess                    = $hasaccess[0];
				if ( ! $hasaccess ) {
					echo pmpro_get_no_access_message( '', $post_membership_levels_ids, $post_membership_levels_names );
				}
			}

			$after_the_content = ob_get_contents();
			ob_end_clean();
			return $original_content . $after_the_content;
		} else {
			return $filtered_content;   // Probably false.
		}

		return $filtered_content; // In case we don't get here.
	}

	/**
	 * Get courses associated with a level.
	 */
	public static function get_courses_for_levels( $level_ids ) {
		global $wpdb;
		
		// In case a level object was passed in.
		if ( is_object( $level_ids ) ) {
			$level_ids = $level_ids->ID;
		}
		
		// Make sure we have an array of ids.
		if ( ! is_array( $level_ids ) ) {
			$level_ids = array( $level_ids );
		}
		
		if ( empty( $level_ids ) ) {
			return array();
		}
		
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
					SELECT mp.page_id 
					FROM $wpdb->pmpro_memberships_pages mp 
					LEFT JOIN $wpdb->posts p ON mp.page_id = p.ID 
					WHERE mp.membership_id IN(%s) 
					AND p.post_type = 'courses' 
					AND p.post_status = 'publish' 
					GROUP BY mp.page_id
				",
				implode(',', $level_ids )
			)
		);		
		
		return $course_ids;
	}

	/**
	 * When users change levels, enroll/unenroll them from
	 * any associated private courses.
	 */
	public static function pmpro_after_all_membership_level_changes( $pmpro_old_user_levels ) {
		foreach ( $pmpro_old_user_levels as $user_id => $old_levels ) {
			// Get current courses.
			$current_levels = pmpro_getMembershipLevelsForUser( $user_id );
			if ( ! empty( $current_levels ) ) {
				$current_levels = wp_list_pluck( $current_levels, 'ID' );
			} else {
				$current_levels = array();
			}
			$current_courses = PMPro_Courses_TutorLMS::get_courses_for_levels( $current_levels );
			
			// Get old courses.
			$old_levels = wp_list_pluck( $old_levels, 'ID' );
			$old_courses = PMPro_Courses_TutorLMS::get_courses_for_levels( $old_levels );
			
			// Unenroll the user in any courses they used to have, but lost.
			$courses_to_unenroll = array_diff( $old_courses, $current_courses );
			foreach( $courses_to_unenroll as $course_id ) {
				if ( tutor_utils()->is_enrolled( $course_id, $user_id ) ) {
					// True param here at the end tells it to remove.
					tutor_utils()->cancel_course_enrol( $course_id, $user_id );
				}
			}

			// Enroll the user in any courses for their current levels.
			$courses_to_enroll = array_diff( $current_courses, $old_courses );
			foreach( $courses_to_enroll as $course_id ) {
				if ( ! tutor_utils()->is_enrolled( $course_id, $user_id ) ) {
					tutor_utils()->do_enroll( $user_id, 0, $course_id );
				}
			}
			
		}
	}	

}
