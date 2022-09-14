<?php
/**
 *
 *
 */

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// See if block editor is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

// Register block type.
function pmpro_courses_register_all_courses_block() {

	wp_register_script( 
		'pmpro-courses-all-courses-block', 
		plugins_url( 'block.build.js', __FILE__ ), 
		array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api', 'wp-editor', 'pmpro_admin' )
	);

	register_block_type( 'pmpro-courses/all-courses', array(
		'editor_script' => 'pmpro-courses-all-courses-block',
		'render_callback' => 'pmpro_courses_shortcode_all_courses'
	) );

}
add_action( 'init', 'pmpro_courses_register_all_courses_block' );