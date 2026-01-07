<?php
/**
 * Register block types for both My Courses and All Courses shortcode.
 * 
 * @since 1.2
 */
function pmpro_courses_register_block_type() {

	register_block_type(
		PMPRO_COURSES_DIR . '/blocks/build/my-courses',
		array(
			'render_callback' => 'pmpro_courses_shortcode_my_courses',
		)
	);

	register_block_type(
		PMPRO_COURSES_DIR . '/blocks/build/all-courses',
		array(
			'render_callback' => 'pmpro_courses_shortcode_all_courses',
		)
	);
}
add_action( 'init', 'pmpro_courses_register_block_type' );
