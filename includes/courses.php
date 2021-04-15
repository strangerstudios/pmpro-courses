<?php 
/**
 * Content filter to show additional course information on the single course page.
 */
function pmpro_courses_the_content_course( $content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {

		// Show a list of lessons from a custom template or the default lesson list after the_content.		
		$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
		$custom_file = $custom_dir."lessons.php";

		// Load custom or default templates.
		if( file_exists($custom_file ) ){
			$include_file = $custom_file;
		} else {
			$include_file = PMPRO_COURSES_DIR . "/templates/lessons.php";
		}

		ob_start();
		include $include_file;
		$after_the_content = ob_get_contents();
		ob_end_clean();

		// Return the content after appending the new post section.
		return $content . $after_the_content;
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
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => $course, 'menu_order' => $order ) );
						
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
			
			wp_update_post( array( 'ID' => $lesson, 'post_parent' => '' ) );
			
			echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			
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

/**
 * Get the edit post link for a course.
 */
function pmpro_courses_get_edit_course_link( $course ) {
	if ( ! is_object( $course ) ) {
		$course = get_post( $course );
	}
	
	if ( empty( $course ) ) {
		return false;
	}
	
	return '<a href="' . esc_url( add_query_arg( array( 'post' => $course->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) . '">' . esc_html( $course->post_title ) . '</a>';
}

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