<?php 
function pmpro_courses_shortcode_courses( $atts ) {

	ob_start();

	$path = dirname(__FILE__);
	$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
	$custom_file = $custom_dir."courses.php";

	$course_limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 5;

	$courses = pmpro_courses_get_courses( $course_limit );

	//load custom or default templates
	if( file_exists($custom_file ) ){
		require_once($custom_file);
	} else {
		require_once($path . "/templates/courses.php");
	}

	$temp_content = ob_get_contents();
	ob_end_clean();

	return $temp_content;

}
add_shortcode( 'pmpro_all_courses', 'pmpro_courses_shortcode_courses' );