<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content filter to show lesson and course information on the single lesson page.
 */
function pmpro_courses_the_content_lesson( $content ) {
	global $post;

	// Return early if not a single lesson page.
	if ( ! is_singular( 'pmpro_lesson' ) ) {
		return $content;
	}

	$course_id = wp_get_post_parent_id( $post->ID );

	ob_start();
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro pmpro_courses', 'pmpro_courses' ) ); ?>">
		<?php
			// Show a link to mark the lesson complete or incomplete.
			$complete_button = pmpro_courses_complete_lesson_button( $post->ID, get_current_user_id() );
			if ( ! empty( $complete_button ) ) {
				$allowed_tags = wp_kses_allowed_html( 'post' );

				// Add aria-pressed to existing button attributes
				if ( isset( $allowed_tags['button'] ) ) {
					$allowed_tags['button']['aria-pressed'] = true;
				}

				// Allow SVG in the complete button.
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
				echo wp_kses( $complete_button, $allowed_tags );
			}
		?>

		<?php if ( ! empty( $course_id ) ) { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses_lesson-back-to-course' ) ); ?>">
				<?php
					/* translators: %s: link to the course for this lesson. */
					printf( esc_html__( 'Course: %s', 'pmpro-courses' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( get_the_title( $course_id ) ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>' );
				?>
			</div> <!-- .pmpro_courses_lesson-back-to-course -->
		<?php } ?>
	</div> <!-- .pmpro_courses -->
	<?php
	$after_the_content = ob_get_clean();
	return $content . $after_the_content;
}
add_filter( 'the_content', 'pmpro_courses_the_content_lesson', 10, 1 );

/**
 * Adds "Course" column to the lessons page
 */
function pmpro_courses_lessons_columns( $columns ) {
	$columns['pmpro_course_assigned'] = esc_html__( 'Course', 'pmpro-courses' );
	$columns['pmpro_course_section'] = esc_html__( 'Section', 'pmpro-courses' );
	return $columns;
}
add_filter( 'manage_pmpro_lesson_posts_columns', 'pmpro_courses_lessons_columns' );

/**
 * Show the course assigned to the lesson.
 *
 * @since 1.0
 *
 */
function pmpro_courses_lessons_columns_content( $column, $post_id ) {
	$lesson_parent = wp_get_post_parent_id( $post_id );
	switch ( $column ) {
		case 'pmpro_course_assigned' :
			if ( empty( $lesson_parent ) ) {
				echo '&mdash;';
			} else {
				echo wp_kses_post( pmpro_courses_get_edit_course_link( wp_get_post_parent_id( $post_id ) ) );
			}
			break;
		case 'pmpro_course_section':
			$sections = get_post_meta( $lesson_parent, 'pmpro_course_sections', true );
			
			// No section found, we must bail.
			if ( empty( $sections ) || ! is_array( $sections ) ) {
				echo '&mdash;';
				return;
			}

			// Get the section name which the lesson belongs to.
			foreach( $sections as $section ) {
				if ( in_array( $post_id, $section['lessons'] ) ) {
					echo ! empty( $section['section_name'] ) ? esc_html( $section['section_name'] ) : '&mdash;';
				}
			}
		break;
	}
}
add_action( 'manage_pmpro_lesson_posts_custom_column' , 'pmpro_courses_lessons_columns_content', 10, 2 );

/**
 * Make "Course" column sortable by parent ID.
 */
function pmpro_courses_lessons_sortable_columns( $columns ) {
    // Map our column to the built-in "parent" orderby.
    $columns['pmpro_course_assigned'] = 'parent';
    return $columns;
}
add_filter( 'manage_edit-pmpro_lesson_sortable_columns', 'pmpro_courses_lessons_sortable_columns' );

/**
 * (Usually optional) Ensure main query honors the "parent" orderby in admin.
 */
function pmpro_courses_lessons_pre_get_posts_table_sorting( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( 'pmpro_lesson' === $query->get( 'post_type' ) && 'parent' === $query->get( 'orderby' ) ) {
        $query->set( 'orderby', 'parent' );
    }
}
add_action( 'pre_get_posts', 'pmpro_courses_lessons_pre_get_posts_table_sorting' );

/**
 * Add a "Course" dropdown filter to the Lessons list table.
 *
 * @since TBD
 */
function pmpro_courses_lessons_filter_dropdown() {
	// Only show on the pmpro_lesson list screen.
	$screen = get_current_screen();
	if ( empty( $screen ) || 'edit-pmpro_lesson' !== $screen->id ) {
		return;
	}

	// Build a dropdown of Courses ordered by title.
	$selected = isset( $_GET['pmpro_courses_filter_course_parent'] ) ? absint( $_GET['pmpro_courses_filter_course_parent'] ) : 0;

	$courses = get_posts( array(
		'post_type'      => 'pmpro_course',
		'post_status'    => 'any',
		'posts_per_page' => 500,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => array( 'ID', 'post_title' )
	) );

	echo '<select name="pmpro_courses_filter_course_parent" id="pmpro_courses_filter_course_parent">';
	echo '<option value="">' . esc_html__( 'All Courses', 'pmpro-courses' ) . '</option>';
	foreach ( $courses as $course ) {
		printf(
			'<option value="%1$d"%3$s>%2$s</option>',
			(int) $course->ID,
			esc_html( $course->post_title ),
			selected( $selected, $course->ID, false )
		);
	}

	echo '</select>';
}
add_action( 'restrict_manage_posts', 'pmpro_courses_lessons_filter_dropdown' );

/**
 * Apply the Course filter to the Lessons query based on the dropdown.
 *
 * @since TBD
 */
function pmpro_courses_lessons_filter_query( WP_Query $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'pmpro_lesson' !== $query->get( 'post_type' ) ) {
		return;
	}

	// Exact-match filter by selected Course ID (parent).
	if ( isset( $_GET['pmpro_courses_filter_course_parent'] ) && '' !== $_GET['pmpro_courses_filter_course_parent'] ) {
		$course_id = absint( $_GET['pmpro_courses_filter_course_parent'] );
		if ( $course_id > 0 ) {
			$query->set( 'post_parent', $course_id );
		}
	}
}
add_action( 'pre_get_posts', 'pmpro_courses_lessons_filter_query' );

/**
 * Bypass any level restrictions for a PMPro Lesson CPT and mark it as "Free/Public"
 *
 * @since TBD
 */
function pmpro_lessons_bypass_check($hasaccess, $post, $user, $levels) {

	// If the person already has access, let's bail.
	if ( $hasaccess ) {
		return $hasaccess;
	}

    if ( get_post_meta( $post->ID, 'pmpro_courses_bypass_restriction', true ) == '1' ) {
        return true;
    }
    return $hasaccess;
}
add_filter('pmpro_has_membership_access_filter_pmpro_lesson', 'pmpro_lessons_bypass_check', 10, 4);
