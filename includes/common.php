<?php
/**
 * Get an array of all PMPro Courses modules.
 * Use the pmpro_courses_modules filter to add your own modules.
 */
function pmpro_courses_get_modules() {
	$modules = array(
		array(
			'name' => esc_html__( 'Default', 'pmpro-courses' ),
			'slug' => 'default',
			'title' => esc_html__( 'The Course and Lesson post types bundled with PMPro Courses.', 'pmpro-courses' ),
			'description' => '<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=plugin&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-default#default-module" target="_blank">' . esc_html__( 'Read the Default Course and Lesson documentation &raquo;', 'pmpro-courses' ) . '</a>',
		)
	);
	$modules = apply_filters( 'pmpro_courses_modules', $modules );

	return $modules;
}

/**
 * Check if a specific module is active.
 */
function pmpro_courses_is_module_active( $module ) {
	$active_modules = get_option( 'pmpro_courses_modules', array() );
	if ( in_array( $module, $active_modules ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Get an array of lessons data assigned to this course ID.
 */
function pmpro_courses_get_lessons( $course ) {
	// Get ID from course if an object is passed in.
	if ( is_object( $course ) ) {
		$course = $course->ID;
	}

	// Return false if no course ID was passed in.
	if ( empty( $course ) ) {
		return false;
	}
	
	// Set up args for query.
	$args = array(
		'post_parent' => $course,
		'posts_per_page' => -1,
		'post_type' => 'pmpro_lesson',
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);
	
	// Return lessons.
	return get_posts( $args );
}

/**
 * Get the next order # for a lesson in a course.
 */
function pmpro_courses_get_next_lesson_order( $course ) {
	// In case a full post object is passed in.
	if ( is_object( $course ) ) {
		$course = $course->ID;
	}
	
	// Get all the lessons.
	$lessons = pmpro_courses_get_lessons( $course );
		
	if ( empty( $lessons ) ) {
		// Default to 1
		return 1;
	} else {
		// Last menu_order + 1
		$last_child = end( $lessons );
		return $last_child->menu_order + 1;
	}
}

/**
 * Get a count of lessons assigned to this course ID.
 */
function pmpro_courses_get_lesson_count( $course_id ) {
	global $wpdb;

	$sql = "SELECT count(*) FROM $wpdb->posts ";
	$sql .= " WHERE post_parent = '" . esc_sql( $course_id ) . "' AND post_type = 'pmpro_lesson'";
	$results = $wpdb->get_var( $sql );
	return intval( $results );
}

/**
 * Get course data for all courses or for a specific user ID's membership levels.
 *
 */
function pmpro_courses_get_courses( $posts_per_page = -1, $user_id = false ) {
	// Set up args for query.
	$args = array(
		'post_type' => 'pmpro_course',
		'posts_per_page' => $posts_per_page,
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);
	
	// Get courses.
	$courses = get_posts( $args );

	if ( ! empty( $user_id ) ) {
		$new_courses = array();
		foreach ( $courses as $course ) {
			if ( pmpro_has_membership_access( $course->ID, $user_id ) ) {
				$new_courses[] = $course;
			}
		}
		$courses = $new_courses;
	}

	return $courses;
}

/**
 * Build the frontend html output for a list of courses with lesson count.
 * 
 * @since 0.1
 */
function pmpro_courses_get_courses_html( $courses ) {
	
	// Return if no array of courses.
	if ( empty( $courses ) ) {
		return;
	}

	ob_start(); ?>
	<div class="pmpro_courses pmpro_courses-courses">
		<h4 class="pmpro_courses-title"><?php esc_html_e( 'Courses', 'pmpro-courses' ); ?></h4>
		<ul class="pmpro_courses-list">
			<?php
				foreach( $courses as $course ) { ?>
					<li id="pmpro_courses-course-<?php echo intval( $course->ID ); ?>" class="pmpro_courses-list-item">
						<a class="pmpro_courses-list-item-link" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>">
							<div class="pmpro_courses-list-item-title">
								<?php echo esc_html( $course->post_title ); ?>
							</div>
							<?php
								$lesson_count = pmpro_courses_get_lesson_count( $course->ID );
								if ( ! empty( $lesson_count ) ) { ?>
								<span class="pmpro_courses-course-lesson-count">
									<?php printf( esc_html( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ) ), number_format_i18n( $lesson_count ) ); ?>
								</span>
							<?php } ?>
						</a>
					</li>
					<?php
				}
			?>
		</ul> <!-- end pmpro_courses-list -->
	</div> <!-- end pmpro_courses -->
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();

	/**
	 * Filter to allow custom code to modify the structure of the frontend courses list.
	 * 
	 */
	$courses_html = apply_filters( 'pmpro_courses_get_courses_html', $temp_content, $courses );

	return $courses_html;
}

/**
 * Build the frontend html output for a list of lessons for a course.
 * 
 * @since 0.1
 */
function pmpro_courses_get_lessons_html( $course_id ) {
	global $post;

	// Get the course ID from the global post.
	if ( empty( $course_id ) && ! empty( $post ) && isset( $post->ID ) ) {
		$course_id = $post->ID;
	}

	// Return if empty.
	if ( empty( $course_id ) ) {
		return;
	}

	ob_start();

	// Get the lessons assigned to this course.
	$lessons = pmpro_courses_get_lessons( $course_id );

	// Return if there are no lessons for this course.
	if ( empty( $lessons ) ) {
		return;
	}

	// Check whether the current user has access to these lessons.
	if ( current_user_can( 'manage_options' ) ) {
		$hasaccess = true;
	} else {
		$hasaccess = pmpro_has_membership_access( $course_id, get_current_user_id() );
	}

	// Set the right class for the lessons list div based on access.
	if ( ! empty( $hasaccess ) ) {
		$pmpro_courses_lesson_access_class = 'pmpro-courses-has-access';
	} else {
		$pmpro_courses_lesson_access_class = 'pmpro-courses-no-access';
	}

	// Build the HTML to output a list of lessons.
	?>
	<div class="pmpro_courses pmpro_courses-lessons <?php echo esc_attr( $pmpro_courses_lesson_access_class ); ?>">
		<h3 class="pmpro_courses-title"><?php esc_html_e( 'Lessons', 'pmpro-courses' ); ?></h3>
		<ol class="pmpro_courses-list">
			<?php
				foreach( $lessons as $lesson ) { ?>
					<li id="pmpro_courses-lesson-<?php echo intval( $lesson->ID ); ?>" class="pmpro_courses-list-item">
						<?php 
							// Only add link to single lesson page if current user has access.
							if ( ! empty( $hasaccess ) ) { ?>
								<a class="pmpro_courses-list-item-link" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
								<?php
							}
						?>
							<div class="pmpro_courses-list-item-title">
								<?php echo esc_html( $lesson->post_title ); ?>
							</div>
							<?php
								if ( is_user_logged_in() && ! empty( $hasaccess ) ) {
									// Get the status of this lesson.
									$lesson_status = pmpro_courses_get_user_lesson_status( $lesson->ID, $post->ID, get_current_user_id() );
									if ( ! empty( $lesson_status ) ) {
										if ( $lesson_status === 'complete' ) {
											echo '<span class="pmpro_courses-lesson-status pmpro_courses-lesson-status-complete"><i class="dashicons dashicons-yes"></i><span class="pmpro_courses-lesson-status-label">' . esc_html( 'Complete', 'pmpro-courses' ) . '</span></span>';
										} else {
											echo '<span class="pmpro_courses-lesson-status pmpro_courses-lesson-status-incomplete"><i class="dashicons dashicons-marker"></i><span class="pmpro_courses-lesson-status-label">' . esc_html( 'Complete', 'pmpro-courses' ) . '</span></span>';
										}
									}
								}
							?>
						<?php 
							// Only add link to single lesson page if current user has access.
							if ( ! empty( $hasaccess ) ) { ?>
								</a>
							<?php
							}
						?>
					</li>
					<?php
				}
			?>
		</ol> <!-- end pmpro_courses-list -->
	</div> <!-- end pmpro_courses -->
	<?php

	$temp_content = ob_get_contents();
	ob_end_clean();

	/**
	 * Filter to allow custom code to modify the structure of the frontend courses list.
	 * 
	 */
	$lessons_html = apply_filters( 'pmpro_courses_get_lessons_html', $temp_content, $course_id, $lessons );

	return $lessons_html;
}
