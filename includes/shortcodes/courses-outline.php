<?php
/**
 * Shortcode to show an outline of the course content.
 * Q: Multiple courses or singular?
 * @since TBD
 */
function pmpro_courses_course_outline( $atts ) {

	// Get all the course ID.
	// Allow course_id, array or singular.
	$course_id = ! empty( $atts['course_id'] ) ? sanitize_text_field( $atts['course_id'] ) : get_the_ID();
	$show_course_title = ! empty( $atts['show_course_title'] ) ? filter_var( $atts['show_course_title'], FILTER_VALIDATE_BOOLEAN ) : true;

	// Nothing passed in or nothing found.
	if ( empty( $course_id ) ) {
		return;
	}

	// Let's make an array.
	if ( ! is_array( $course_id ) ) {
		$course_id = array( $course_id );
	}

	$course_outline = '';
	foreach( $course_id as $id ) {
		
		if ( get_post_type( $id ) !== 'pmpro_course' ) {
			continue;
		}

		if ( $show_course_title ) {
			$course_outline .= '<h1><a href="' . get_permalink( $id ) . '">' . get_post( $id )->post_title . '</a></h1>';
		}
		$course_outline .= pmpro_courses_get_lessons_html( $id );
	}

	return $course_outline;
}
add_shortcode( 'pmpro_course_outline', 'pmpro_courses_course_outline' );