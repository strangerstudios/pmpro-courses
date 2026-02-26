<?php
/**
 * Shortcode to show the course outline for one or more courses.
 *
 * Shortcode attributes:
 * - course_id: The ID(s) of the course(s) to display. Can be a single ID, an array of IDs, or a comma-separated string of IDs.
 * - show_course_title: Whether to display the course title. Default is true.
 *
 * @since TBD
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pmpro_courses_course_outline( $atts ) {
	// Get all the course ID.
	// Allow course_id, array or singular.
	$course_id = ! empty( $atts['course_id'] ) ? $atts['course_id'] : get_the_ID();
	$show_course_title = ! empty( $atts['show_course_title'] ) ? filter_var( $atts['show_course_title'], FILTER_VALIDATE_BOOLEAN ) : true;

	// Nothing passed in or nothing found.
	if ( empty( $course_id ) ) {
		return;
	}

	// Let's make an array.
	if ( ! is_array( $course_id ) ) {
		// Split by comma if it's a comma-separated string
		$course_id = array_map( 'trim', explode( ',', $course_id ) );
	}

	// Sanitize each ID
	$course_id = array_map( 'absint', $course_id );
	// Remove any invalid IDs (0 values)
	$course_id = array_filter( $course_id );

	ob_start();

	foreach ( $course_id as $id ) {
		
		if ( get_post_type( $id ) !== 'pmpro_course' ) {
			continue;
		}

		if ( $show_course_title ) { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro pmpro_courses', 'pmpro_courses' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-x-large' ) ); ?>">
					<a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_post( $id )->post_title ); ?></a></h2>
			</div>
		<?php }

		// Allow SVG in the course outline.
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags = array_merge(
			$allowed_tags,
			array(
				'svg' => array(
					'class' => true,
					'aria-hidden' => true,
					'xmlns' => true,
					'width' => true,
					'height' => true,
					'viewBox' => true,
				),
				'use' => array(
					'href' => true,
				)
			)
		);
		echo wp_kses( pmpro_courses_get_lessons_html( $id ), $allowed_tags );
	}

	$course_outline = ob_get_clean();
	return $course_outline;
}
add_shortcode( 'pmpro_course_outline', 'pmpro_courses_course_outline' );