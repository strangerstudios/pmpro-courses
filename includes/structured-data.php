<?php
/**
 * Structured data (JSON-LD) for the default Courses module.
 * Outputs Course schema on single course pages and ItemList schema
 * on the CPT archive and pages using the [pmpro_all_courses] shortcode.
 *
 * @since 1.3
 */

/**
 * Output Course JSON-LD structured data on single course pages.
 *
 * @since 1.3
 */
function pmpro_courses_structured_data_single() {
	if ( ! is_singular( 'pmpro_course' ) ) {
		return;
	}

	$post = get_post();
	if ( empty( $post ) ) {
		return;
	}

	$schema = pmpro_courses_build_course_schema( $post );

	?>
	<script type="application/ld+json">
	<?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
	</script>
	<?php
}
add_action( 'wp_head', 'pmpro_courses_structured_data_single' );

/**
 * Output ItemList JSON-LD structured data on course listing pages.
 * Fires on the CPT archive and on pages containing the [pmpro_all_courses] shortcode.
 *
 * @since 1.3
 */
function pmpro_courses_structured_data_listing() {
	// CPT archive.
	$is_archive = is_post_type_archive( 'pmpro_course' );

	// Page with [pmpro_all_courses] shortcode.
	$is_shortcode_page = false;
	if ( is_page() ) {
		$post = get_post();
		if ( ! empty( $post ) && ( has_shortcode( $post->post_content, 'pmpro_all_courses' ) || has_block( 'pmpro-courses/all-courses', $post ) ) ) {
			$is_shortcode_page = true;
		}
	}

	if ( ! $is_archive && ! $is_shortcode_page ) {
		return;
	}

	$courses = pmpro_courses_get_courses();
	if ( empty( $courses ) ) {
		return;
	}

	$item_list_elements = array();
	$position           = 1;

	foreach ( $courses as $course ) {
		$item_list_elements[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'url'      => get_permalink( $course->ID ),
			'item'     => pmpro_courses_build_course_schema( $course ),
		);
		$position++;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $item_list_elements,
	);

	?>
	<script type="application/ld+json">
	<?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
	</script>
	<?php
}
add_action( 'wp_head', 'pmpro_courses_structured_data_listing' );

/**
 * Build a Course schema array for a given course post.
 *
 * @since 1.3
 * @param WP_Post $course The course post object.
 * @return array Course schema array.
 */
function pmpro_courses_build_course_schema( $course ) {
	// Description: excerpt first, fallback to trimmed content.
	$description = $course->post_excerpt;
	if ( empty( $description ) ) {
		$description = wp_trim_words( $course->post_content, 20, '...' );
	}

	/**
	 * Filter the provider organization data for Course structured data.
	 *
	 * @since 1.3
	 * @param array   $provider {
	 *     Provider organization schema.
	 *     @type string $@type  Schema type. Default 'Organization'.
	 *     @type string $name   Organization name. Default site name.
	 *     @type string $sameAs Organization URL. Default home URL.
	 * }
	 * @param WP_Post $course The course post object.
	 */
	$provider = apply_filters(
		'pmpro_courses_structured_data_provider',
		array(
			'@type'  => 'Organization',
			'name'   => get_bloginfo( 'name' ),
			'sameAs' => home_url(),
		),
		$course
	);

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Course',
		'name'        => get_the_title( $course->ID ),
		'description' => wp_strip_all_tags( $description ),
		'url'         => get_permalink( $course->ID ),
		'provider'    => $provider,
	);

	/**
	 * Filter the full Course schema array.
	 *
	 * @since 1.3
	 * @param array   $schema The Course schema array.
	 * @param WP_Post $course The course post object.
	 */
	return apply_filters( 'pmpro_courses_structured_data_schema', $schema, $course );
}
