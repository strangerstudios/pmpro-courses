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
	), $atts));

	ob_start();

	if ( ! is_user_logged_in() ) {
		return '<p><a href="<?php echo esc_url( wp_login_url() ); ?>">' . esc_html_e( 'Log in to view your courses »', 'paid-memberships-pro') . '</a></p>';
	}

	$course_limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 5;

	$courses = pmpro_courses_get_courses( $course_limit, get_current_user_id() );

	return pmpro_courses_get_courses_html( $courses );
}
add_shortcode( 'pmpro_my_courses', 'pmpro_courses_shortcode_my_courses' );
