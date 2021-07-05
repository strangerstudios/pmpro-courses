<?php
/**
 * Content filter to show lesson and course information on the single lesson page.
 */
function pmpro_courses_the_content_lesson( $content ) {
	global $post;
	if ( is_singular( 'pmpro_lesson' ) ) {
		$course_id = wp_get_post_parent_id( $post->ID );

		$after_the_content = '';

		// Show a link to mark the lesson complete or incomplete.	
		$complete_button = pmpro_courses_complete_button( $post->ID, $course_id );
		if ( ! empty( $complete_button ) ) {			
			$after_the_content .= '<div class="pmpro_courses_lesson-status">';
			$after_the_content .= $complete_button;
			$after_the_content .= '</div>';			
		}

		if ( ! empty( $course_id ) ) {
			$after_the_content .= sprintf(
				/* translators: %s: link to the course for this lesson. */
				'<div class="pmpro_courses_lesson-back-to-course">' . esc_html__( 'Course: %s', 'pmpro-courses' ) . '</div>',
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
    $columns['pmpro_course_assigned'] = esc_html__( 'Course', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_lesson_posts_columns', 'pmpro_courses_lessons_columns' );

function pmpro_courses_lessons_columns_content( $column, $post_id ) {
	switch ( $column ) {
		case 'pmpro_course_assigned' :
			$lesson_parent = wp_get_post_parent_id( $post_id );
			if ( empty( $lesson_parent ) ) {
				esc_html_e( '&#8212;', 'pmpro-courses' );
			} else {
				echo pmpro_courses_get_edit_course_link( wp_get_post_parent_id( $post_id ) ); 
			}
			break;
	}
}
add_action( 'manage_pmpro_lesson_posts_custom_column' , 'pmpro_courses_lessons_columns_content', 10, 2 );

/**
 * Hide some prev/next links for lessons.
 * We only want to show links for lessons in the same course.
 * Hook in on init and remove_action(...) to disable this.
 * @since .1
 */
function pmpro_courses_hide_adjacent_post_links_for_lessons( $output, $format, $link, $adjacent_post, $adjacent ) {
	global $post;
	
	// No post or adjacent post. Probably no link.
	if ( empty( $post ) || empty( $adjacent_post ) ) {
		return $output;
	}
	
	// Not a lesson. Bail.
	if ( empty( $post->post_type ) || $post->post_type != 'pmpro_lesson' ) {
		return $output;
	}
	
	// Lesson without a course. Hide the link.
	if ( empty( $post->post_parent ) || $post->post_parent == $post->ID ) {
		return '';
	}
	
	// Lessons from different courses. Hide the link.
	if ( $post->post_parent !== $adjacent_post->post_parent ) {
		return '';
	}
		
	return $output;
}
add_action( 'previous_post_link', 'pmpro_courses_hide_adjacent_post_links_for_lessons', 10, 5 );
add_action( 'next_post_link', 'pmpro_courses_hide_adjacent_post_links_for_lessons', 10, 5 );