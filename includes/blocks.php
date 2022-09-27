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
			'editor_script' => 'pmpro-block-my-courses-js',
			'render_callback' => 'pmpro_courses_shortcode_my_courses',
		)
	);

	register_block_type(
		PMPRO_COURSES_DIR . '/blocks/build/all-courses',
		array(
			'editor_script' => 'pmpro-block-all-courses-js',
			'render_callback' => 'pmpro_courses_shortcode_all_courses',
		)
	);
}
add_action( 'init', 'pmpro_courses_register_block_type' );

/**
 * Enqueue the Block Scripts for both blocks.
 *
 * @since 1.2
 */
function pmpro_courses_block_scripts() {
	wp_enqueue_script(
		'pmpro-block-my-courses-js',
		plugins_url( 'blocks/build/my-courses/index.js', __DIR__ ),
		plugins_url( 'blocks/build/my-courses/index.asset.php')
	);

	wp_enqueue_script(
		'pmpro-block-all-courses-js',
		plugins_url( 'blocks/build/all-courses/index.js', __DIR__ ),
		plugins_url( 'blocks/build/all-courses/index.asset.php')
	);
}
add_action( 'enqueue_block_editor_assets', 'pmpro_courses_block_scripts' );