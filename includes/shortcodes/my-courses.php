<?php
/**
 * Shortcode to show courses available for my memberships in the default courses module.
 *
 * @since 0.1.0
 *
 */
function pmpro_courses_shortcode_my_courses( $atts ) {

	extract( shortcode_atts( array(
		'limit' => -1,
		'hide_inactive' => false,
		'show_inactive' => false
	), $atts));

	// User not logged-in, output the login link.
	if ( ! is_user_logged_in() ) {
		return '<p><a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Log in to view your courses »', 'paid-memberships-pro') . '</a></p>';
	}

	// Sanitize the shortcode attributes.
	$course_limit = intval( $limit );
	$hide_inactive = filter_var( $hide_inactive, FILTER_VALIDATE_BOOLEAN );
	$show_inactive = filter_var( $show_inactive, FILTER_VALIDATE_BOOLEAN );

	// Get all the courses available for the current user.
	$courses = pmpro_courses_get_courses( $course_limit, get_current_user_id() );

	// Hide empty courses from the list.
	if ( $hide_inactive ) {
		$empty_progress_courses = PMPro_Courses_User_Progress::pmpro_courses_get_no_activity_courses_for_user( get_current_user_id() ); // returns an array of objects.
		
		foreach( $courses as $key => $course ) {
			foreach( $empty_progress_courses as $empty_course ) {
				if ( $course->ID == $empty_course->ID ) {
					unset( $courses[$key] );
				}
			}
		}
	}

	// Only show inactive courses, override the courses.
	if ( $show_inactive ) {
		$courses = PMPro_Courses_User_Progress::pmpro_courses_get_no_activity_courses_for_user( get_current_user_id() ); // returns an array of objects.
	}
	
	return pmpro_courses_get_courses_html( $courses );
}
add_shortcode( 'pmpro_my_courses', 'pmpro_courses_shortcode_my_courses' );
