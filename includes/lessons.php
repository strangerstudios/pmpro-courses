<?php
/**
 * Content filter to show lesson and course information on the single lesson page.
 */
function pmpro_courses_the_content_lesson( $content ) {
	global $post;
	if ( is_singular( 'pmpro_lesson' ) ) {
		$course_id = get_post_meta( $post->ID, 'pmproc_parent', true );

		// This is a single pmpro_lesson CPT, show additional content before the_content.
		$before_the_content = '';
		if ( ! empty( $course_id ) ) {
			$before_the_content .= sprintf(
				/* translators: %s: link to the course for this lesson. */
				'<p>' . esc_html__( 'Back to: %s', 'pmpro-courses' ) . ' </span></p>',
				'<a href="' . get_permalink( $course_id ) . '" title="' . get_the_title( $course_id ) . '">' . get_the_title( $course_id ) . '</a>'
			);
		}

		$after_the_content = '<hr class="styled-separator is-style-wide" aria-hidden="true" />';

		// Show a link to mark the lesson complete or incomplete.
		$show_complete_button = apply_filters( 'pmproc_show_complete_button', true );
		if ( $show_complete_button ) {
			$lesson_status = pmproc_get_user_lesson_status( $post->ID, $course_id );
			if ( ! empty( $lesson_status ) ) {
				$after_the_content .= '<div class="pmpro_lesson-status">';
				if ( $lesson_status === 'complete' ) {
					// Filter the text shown as the button title for a completed lesson.
					$after_the_content .= esc_html( apply_filters( 'pmproc_lesson_complete_text', __('Lesson Completed', 'pmpro-courses' ) ) );
				} else {
					// Filter the text shown as the button title to mark a lesson complete.
					$after_the_content .= esc_html( apply_filters( 'pmproc_lesson_to_complete_text', __('Mark Lesson as Complete', 'pmpro-courses') ) );
				}
				$after_the_content .= pmproc_complete_button( $post->ID, $course_id );
				$after_the_content .= '</div>';
				$after_the_content .= '<hr class="styled-separator is-style-wide" aria-hidden="true" />';
			}
		}

		// Show a link to the previous and next lesson in the course.
		$show_lesson_navigation = apply_filters( 'pmproc_show_lesson_navigation', true );
		if ( $show_lesson_navigation ) {
			$pmproc_lesson_navigation_prev = pmproc_lesson_navigation( $post->ID, $course_id, 'prev' );
			$pmproc_lesson_navigation_next = pmproc_lesson_navigation( $post->ID, $course_id, 'next' );

			$after_the_content .= '<nav class="pmpro_lesson-navigation" role="navigation">';
			if ( ! empty( $pmproc_lesson_navigation_prev ) ) {
				$after_the_content .= '<div class="nav-previous"><span class="pmpro_lesson-navigation-label">' . __( 'Previous Lesson', 'pmpro-courses' ) . '</span>' . $pmproc_lesson_navigation_prev . '</div>';
			}
			if ( ! empty( $pmproc_lesson_navigation_next ) ) {
				$after_the_content .= '<div class="nav-next"><span class="pmpro_lesson-navigation-label">' . __( 'Next Lesson', 'pmpro-courses' ) . '</span>' . $pmproc_lesson_navigation_next . '</div>';
			}
			$after_the_content .= '</nav>';
		}

		return $before_the_content . $content . $after_the_content;
	}
	return $content;
}
add_filter( 'the_content', 'pmpro_courses_the_content_lesson', 10, 1 );

/**
 * Adds "Course" column to the lessons page
 */
function pmpro_courses_lessons_columns( $columns ) {
    $columns['pmpro_course_assigned'] = __( 'Course', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_lesson_posts_columns', 'pmpro_courses_lessons_columns' );

function pmpro_courses_lessons_columns_content( $column, $post_id ) {
    switch ( $column ) {
        case 'pmpro_course_assigned' :
            echo pmpro_courses_get_course( $post_id ); 
            break;
    }
}
add_action( 'manage_pmpro_lesson_posts_custom_column' , 'pmpro_courses_lessons_columns_content', 10, 2 );