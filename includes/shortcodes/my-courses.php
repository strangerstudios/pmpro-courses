<?php
/**
 * Shortcode to show courses available for my memberships in the default courses module.
 *
 * @since 0.1.0
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_courses_shortcode_my_courses( $atts ) {

	$atts = shortcode_atts( array(
		'limit' => -1,
		'filter' => 'all' // Options: 'all', 'active', 'inactive'
	), $atts );

	// User not logged-in, output the login link.
	if ( ! is_user_logged_in() ) {
		return '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro pmpro_courses', 'pmpro_courses' ) ) . '">
			<p class="' . esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_message-info' ) ) . '">
				<a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Log in to view your courses', 'pmpro-courses' ) . '</a>
			</p>
		</div>';
	}

	// Sanitize the shortcode attributes.
	$course_limit = intval( $atts['limit'] );
	$filter = sanitize_text_field( $atts['filter'] );

	// Apply filter based on activity.
	if ( $filter === 'inactive' ) {
		// Only show courses with zero progress.
		$courses = PMPro_Courses_User_Progress::pmpro_courses_get_no_activity_courses_for_user( get_current_user_id() );
	} else {
		// Get all the courses available for the current user.
		$courses = pmpro_courses_get_courses( $course_limit, get_current_user_id() );

		if ( $filter === 'active' ) {
			// Hide courses with zero progress.
			$empty_progress_courses = PMPro_Courses_User_Progress::pmpro_courses_get_no_activity_courses_for_user( get_current_user_id() );
			$empty_course_ids = array_map( 'intval', wp_list_pluck( $empty_progress_courses, 'ID' ) );

			// Filter out courses that have no activity
			foreach ( $courses as $key => $course ) {
				if ( in_array( $course->ID, $empty_course_ids, true ) ) {
					unset( $courses[ $key ] );
				}
			}
			// Re-index array
			$courses = array_values( $courses );
		}
	}

	return pmpro_courses_get_courses_html( $courses );
}
add_shortcode( 'pmpro_my_courses', 'pmpro_courses_shortcode_my_courses' );
