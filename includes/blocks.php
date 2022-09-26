<?php
/**
 * Block type for "my-courses"
 * 
 * @since TBD
 */
function pmpro_courses_register_block_type() {
	register_block_type(
		__DIR__ . '/blocks/build/my-courses',
		array(
			'editor_script' => 'pmpro-block-my-courses-js',
			'render_callback' => 'pmpro_courses_my_courses_callback',
		)
	);
}
add_action( 'init', 'pmpro_courses_register_block_type' );

/**
 * Callback that is a wrapper for the pmpro_my_courses shortcode.
 * 
 * @since TBD
 */
function pmpro_courses_my_courses_callback( $attributes ) {
	return "ANDREW";
	// echo "[pmpro_my_courses limit='" . (int) $attributes['limit'] . "']";
    // return do_shortcode( "[pmpro_my_courses limit='" . (int) $attributes['limit'] . "']" );
}

/**
 * Enqueue the Block Scripts here.
 *
 * @since TBD
 */
function pmpro_courses_block_scripts() {
	wp_enqueue_script(
		'pmpro-block-my-courses-js',
		plugins_url( 'blocks/build/my-courses/index.js', __DIR__ ),
		[ 'wp-edit-post', 'wp-element', 'wp-components', 'wp-plugins', 'wp-data' ]
	);
}
add_action( 'enqueue_block_editor_assets', 'pmpro_courses_block_scripts' );