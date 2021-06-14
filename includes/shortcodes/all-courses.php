<?php
/**
 * Shortcode to show all available courses in the default courses module.
 *
 * @since 0.1.0
 *
 */
function pmpro_courses_shortcode_all_courses( $atts ) {

	extract( shortcode_atts( array(
		'limit' => -1,
	), $atts));

	$course_limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : -1;

	$courses = pmpro_courses_get_courses( $course_limit );

	return pmpro_courses_get_courses_html( $courses );

}
add_shortcode( 'pmpro_all_courses', 'pmpro_courses_shortcode_all_courses' );