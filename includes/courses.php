<?php 
/**
 * Content filter to show additional course information on the single course page.
 */
function pmpro_courses_the_content_course( $content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {
		// This is a single pmpro_course CPT, show additional content before and after post_content.
		$before_the_content = '';
		$course_categories_list = get_the_term_list( $post->ID, 'pmpro_course_category', '', __( ', ', 'pmpro-courses' ) );
		if ( $course_categories_list ) {
			$before_the_content .= sprintf(
				/* translators: %s: list of categories. */
				'<p><span class="cat-links">' . esc_html__( 'Course Category: %s', 'pmpro-courses' ) . ' </span></p>',
				$course_categories_list
			);
		}

		$show_progress_bar = apply_filters( 'pmproc_show_progress_bar', true );
		if ( $show_progress_bar ) {
			$before_the_content .= pmproc_display_progress_bar( $post->ID );
		}

		// Show a list of lessons from a custom template or the default lesson list after the_content.
		$path = dirname(__FILE__);
		$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
		$custom_file = $custom_dir."lessons.php";

		// Load custom or default templates.
		if( file_exists($custom_file ) ){
			$include_file = $custom_file;
		} else {
			$include_file = $path . "/templates/lessons.php";
		}

		ob_start();
		include $include_file;
		$after_the_content = ob_get_contents();
		ob_end_clean();

		// Return the content after appending new pre and post sections.
		return $before_the_content . $content . $after_the_content;
	}
	return $content;
}
add_filter( 'the_content', 'pmpro_courses_the_content_course', 10, 1 );

function pmpro_courses_update_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_update_course' ){
			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$order = intval( $_REQUEST['order'] );
			
			// If no order, set to the max.
			if ( empty( $order ) ) {
				$order = pmproc_get_next_lesson_order( $course );
			}			
			
			update_post_meta( $lesson, 'pmproc_parent', $course );
			wp_update_post( array( 'ID' => $lesson, 'menu_order' => $order ) );
			
			echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			
			wp_die();
		}

	}

}
add_action( 'wp_ajax_pmproc_update_course', 'pmpro_courses_update_course_callback' );

function pmpro_courses_remove_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_remove_course' ){

			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$deleted = delete_post_meta( $lesson, 'pmproc_parent' );

			if( $deleted ){
				echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			} else {
				echo 'error';
			}
			wp_die();
		}
	}

}
add_action( 'wp_ajax_pmproc_remove_course', 'pmpro_courses_remove_course_callback' );

/**
 * Adds columns to the Courses table.
 */
function pmpro_courses_columns($columns) {
    $columns['pmpro_courses_num_lessons'] = __( 'Lesson Count', 'pmpro-courses' );
    $columns['pmpro_courses_level'] = __( 'Level', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_course_posts_columns', 'pmpro_courses_columns' );

function pmpro_courses_columns_content( $column, $course_id ) {
	global $wpdb;
	switch ( $column ) {
		case 'pmpro_courses_num_lessons' :
			$lesson_count = pmpro_courses_get_lesson_count( $course_id );
			printf( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ), number_format_i18n( $lesson_count ) );
			break;
		case 'pmpro_courses_level' :
			$membership_levels = pmpro_getAllLevels( true, true );
			$course_levels = $wpdb->get_col( "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $course_id ) . "'" );
			$level_names = array();
			foreach ( $course_levels as $id ) {
				$level = pmpro_getLevel( $id );
				$level_names[] = $level->name;
			}
			if ( ! empty( $level_names ) ) {
				echo implode( ', ', $level_names );
			} else {
				echo '&#8212;';
			}
			break;
	}
}
add_action( 'manage_pmpro_course_posts_custom_column' , 'pmpro_courses_columns_content', 10, 2 );

function pmpro_courses_template_redirect() {

	global $post, $pmpro_pages;

	if( !empty( $post ) ){

		if( ( $post->post_type == 'pmpro_course' || $post->post_type == 'pmpro_lesson' ) && !is_archive() && !current_user_can( 'administrator' ) ){

			$post_id = $post->ID;

			//Choose a courses page to redirect to or go to the levels page? 	
			$redirect_to = apply_filters( 'pmpro_courses_redirect_to', pmpro_url( 'levels' ) );

			$access = pmpro_courses_check_level( $post_id );

			if( !$access && ( intval( $pmpro_pages['levels'] ) !== $post_id ) ){
				wp_redirect( $redirect_to );
				exit();
			}

		}

	}

}
add_action( 'template_redirect', 'pmpro_courses_template_redirect' );

function pmproc_has_course_access( $hasaccess, $mypost, $myuser, $post_membership_levels ) {

	if ( 'pmpro_courses' == $mypost->post_type ) {
		$hasaccess = pmpro_courses_check_level( $mypost->ID );
	}

	return $hasaccess;
}
add_filter( 'pmpro_has_membership_access_filter', 'pmproc_has_course_access', 99, 4 );

function pmproc_course_links_my_account() {
	global $pmpro_pages;
	if ( isset( $pmpro_pages['pmpro_my_courses'] ) ) {
		?>
		<li>
			<a href="<?php the_permalink( $pmpro_pages['pmpro_my_courses'] ); ?>"><?php _e('My Courses', 'pmpro-courses'); ?></a>
		</li>
		<?php
	}
}
add_action( 'pmpro_member_links_bottom', 'pmproc_course_links_my_account' );