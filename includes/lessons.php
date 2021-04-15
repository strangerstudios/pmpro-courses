<?php
/**
 * Content filter to show lesson and course information on the single lesson page.
 */
function pmpro_courses_the_content_lesson( $content ) {
	global $post;
	if ( is_singular( 'pmpro_lesson' ) ) {
		$course_id = wp_get_post_parent_id( $post->ID );

		$after_the_content = '<hr class="styled-separator is-style-wide" aria-hidden="true" />';

		// Show a link to mark the lesson complete or incomplete.
		$show_complete_button = apply_filters( 'pmpro_courses_show_complete_button', true );
		if ( $show_complete_button ) {
			$lesson_status = pmpro_courses_get_user_lesson_status( $post->ID, $course_id );
			if ( ! empty( $lesson_status ) ) {
				$after_the_content .= '<div class="pmpro_courses_lesson-status">';
				$after_the_content .= '<p><input name="pmpro_courses_lesson" type="checkbox" /> <label for="pmpro_courses_lesson' . esc_attr( $post->ID ) . '_toggle">Mark Complete</label></p>';
				$after_the_content .= '<p><input name="pmpro_courses_lesson" type="checkbox" checked="checked" /> <label for="pmpro_courses_lesson' . esc_attr( $post->ID ) . '_toggle">Completed</label></p>';
				//$after_the_content .= pmpro_courses_complete_button( $post->ID, $course_id );
				//$after_the_content .= '<label for="pmpro_courses_lesson' . esc_attr( $post->ID ) . '_toggle">' . __( 'Completed?', 'pmpro-courses' ) . '</label>';
				$after_the_content .= '</div>';
				$after_the_content .= '<hr class="styled-separator is-style-wide" aria-hidden="true" />';
			}
		}

		if ( ! empty( $course_id ) ) {
			$after_the_content .= sprintf(
				/* translators: %s: link to the course for this lesson. */
				'<p>' . esc_html__( 'Course: %s', 'pmpro-courses' ) . ' </span></p>',
				'<a href="' . get_permalink( $course_id ) . '" title="' . get_the_title( $course_id ) . '">' . get_the_title( $course_id ) . '</a>'
			);
		}
		
		return $content . $after_the_content;
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
            echo pmpro_courses_get_edit_course_link( wp_get_post_parent_id( $post_id ) ); 
            break;
    }
}
add_action( 'manage_pmpro_lesson_posts_custom_column' , 'pmpro_courses_lessons_columns_content', 10, 2 );
