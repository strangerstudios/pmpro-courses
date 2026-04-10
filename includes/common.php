<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 *
 * @param int|object $course The course ID or post object.
 * @param array      $args   Optional. Additional args for get_posts(). Default is null
 *
 * @return array|false Array of lesson post objects or false if no course ID is passed in.
 */
function pmpro_courses_get_lessons( $course, $args = array() ) {
	// Get ID from course if an object is passed in.
	if ( is_object( $course ) ) {
		$course = $course->ID;
	}

	// Return false if no course ID was passed in.
	if ( empty( $course ) ) {
		return false;
	}

	// Set up default args for query.
	$defaults = array(
		'post_parent'    => $course,
		'posts_per_page' => 99,
		'post_type'      => 'pmpro_lesson',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

	// Merge passed args with defaults (passed args take priority).
	$args = wp_parse_args( $args, $defaults );

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
 *
 * @since 1.0
 *
 * @param int $course_id The course ID.
 * @param string|array $post_status Optional. The post status or array of post statuses to count. Default array( 'publish' ).
 * @return int The number of lessons.
 */
function pmpro_courses_get_lesson_count( $course_id, $post_status = array( 'publish' ) ) {
	global $wpdb;

	// Ensure $post_status is always an array.
	if ( ! is_array( $post_status ) ) {
		$post_status = array( $post_status );
	}

	// Sanitize statuses for IN clause.
	$post_status = array_map( 'esc_sql', $post_status );
	$status_list = "'" . implode( "','", $post_status ) . "'";

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = %s AND post_status IN ($status_list)",
			$course_id,
			'pmpro_lesson'
		)
	);

	return intval( $count );
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
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro pmpro_courses', 'pmpro_courses' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_courses-courses', 'pmpro_courses-courses' ) ); ?>">
			<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-x-large' ) ); ?>"><?php esc_html_e( 'Courses', 'pmpro-courses' ); ?></h2>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list' ) ); ?>">
					<?php foreach ( $courses as $course ) { ?>
						<li id="pmpro_courses-course-<?php echo intval( $course->ID ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item' ) ); ?>">
							<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item-link' ) ); ?>" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>">
								<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item-title' ) ); ?>"><?php echo esc_html( $course->post_title ); ?></span>
								<?php
									// Show progress if user is logged in and has started the course.
									if ( is_user_logged_in() ) {
										$progress = PMPro_Courses_User_Progress::get_course_progress_for_user( $course->ID, get_current_user_id() );
										if ( $progress > 0 ) {
											?>
											<span class="screen-reader-text">
												<?php
													/* translators: %s is the course name. */
													printf( esc_html__( 'Progress for course %s', 'pmpro-courses' ), esc_html( $course->post_title )
													);
												?>
											</span>
											<?php
												// Build the selectors for the status tag.
												$progress_classes = array();
												$progress_classes[] = 'pmpro_tag';
												if ( $progress == 100 ) {
													$progress_classes[] = 'pmpro_tag-success';
												} else {
													$progress_classes[] = 'pmpro_tag-alert';
												}
												$progress_class = implode( ' ', $progress_classes );
											?>
											<span class="<?php echo esc_attr( pmpro_get_element_class( $progress_class ) ); ?>">
												<?php echo esc_html( $progress ) . '%'; ?>
											</span>
											<?php
										}
									}
								?>
								<?php
									$lesson_count = pmpro_courses_get_lesson_count( $course->ID );
									if ( ! empty( $lesson_count ) ) { ?>
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-course-lesson-count' ) ); ?>">
											<?php
												/* translators: %s is the number of lessons. */
												printf( esc_html( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ) ), esc_html( number_format_i18n( $lesson_count ) ) );
											?>
										</span>
										<?php
									}
								?>
							</a>
						</li>
					<?php } ?>
				</ul> <!-- end pmpro_courses-list -->
			</div> <!-- end pmpro_card_content -->
		</div> <!-- end pmpro_courses-courses -->
	</div> <!-- end pmpro_courses -->
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();

	/**
	 * Filter to allow custom code to modify the structure of the frontend courses list.
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

	// Get the course outline (sections with lessons).
	$sections = get_post_meta( $course_id, 'pmpro_course_sections', true );

	// If no course outline, get all lessons for the course.
	if ( empty( $sections ) ) {
		$sections = array();
		$sections[]['lessons'] = pmpro_courses_get_lessons( $course_id, array( 'post_status' => 'publish' ) );
	}

	// If a section is empty, remove it.
	foreach ( $sections as $key => $section ) {
		if ( empty( $section['lessons'] ) ) {
			unset( $sections[ $key ] );
		}
	}

	// Return if no sections.
	if ( empty( $sections ) ) {
		return;
	}

	// Check whether the current user has access to these lessons.
	if ( current_user_can( 'manage_options' ) ) {
		$hasaccess = true;
	} else {
		$hasaccess = pmpro_has_membership_access( $course_id, get_current_user_id() );
	}

	// Set up classes for lessons container.
	$pmpro_courses_lessons_classes = array();
	$pmpro_courses_lessons_classes[] = 'pmpro_courses-course-outline';
	if ( ! empty( $hasaccess ) ) {
		$pmpro_courses_lessons_classes[] = 'pmpro_courses-has-access';
	} else {
		$pmpro_courses_lessons_classes[] = 'pmpro_courses-no-access';
	}
	$pmpro_courses_lessons_class = implode( ' ', $pmpro_courses_lessons_classes );

	// Build the HTML to output a list of lessons.
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro pmpro_courses', 'pmpro_courses' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( $pmpro_courses_lessons_class ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-x-large' ) ); ?>"><?php esc_html_e( 'Course Outline', 'pmpro-courses' ); ?></h2>
				<?php 
          $section_id = 1;
          foreach ( $sections as $section ) {
					// Filter out non-published lessons so we can skip empty sections.
					$published_lessons = array();
					foreach ( $section['lessons'] as $lesson_id ) {
						$lesson = get_post( $lesson_id );
						if ( ! empty( $lesson ) && $lesson->post_status === 'publish' ) {
							$published_lessons[] = $lesson;
						}
					}

					// Skip this section entirely if it has no published lessons.
					if ( empty( $published_lessons ) ) {
						continue;
					}
            
           // If we don't have an ID, let's just use an incrementing number. This allows for sections to be added without an ID and still have a unique ID for the HTML.
					if ( empty( $section['section_id'] ) ) {
						$section['section_id'] = $section_id;
					}
					$section_id++;

					// If section name is empty, show as Section X, where X is the section number.
					if ( empty( $section['section_name'] ) ) {
						/* translators: %d: section number (integer) */
						$section['section_name'] = sprintf( esc_html__( 'Section %d', 'pmpro-courses' ), intval( $section['section_id'] ) );
					}
					?>
					<div id="pmpro_courses-section-<?php echo intval( $section['section_id'] ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title' ) ); ?>">
							<h3>
								<button id="pmpro_courses-section-toggle-<?php echo intval( $section['section_id'] ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" type="button" aria-controls="pmpro_courses-section-lessons-<?php echo intval( $section['section_id'] ); ?>" aria-expanded="true">
									<?php echo esc_html( $section['section_name'] ); ?>
									<svg class="<?php echo( esc_attr( pmpro_get_element_class( 'pmpro_courses-feather-icon pmpro_courses-feather-icon-chevron-up' ) ) ); ?>" aria-hidden="true">
										<use href="<?php echo esc_url( PMPRO_COURSES_URL . 'images/feather-sprite.svg#chevron-up' ); ?>"></use>
									</svg>
								</button>
							</h3>
						</div> <!-- end pmpro_card_title -->
						<div id="pmpro_courses-section-lessons-<?php echo intval( $section['section_id'] ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content pmpro_courses-lessons', 'pmpro_courses-lessons' ) ); ?>" role="region" aria-labelledby="pmpro_courses-section-toggle-<?php echo intval( $section['section_id'] ); ?>">
							<ol class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list' ) ); ?>">
								<?php foreach ( $published_lessons as $lesson ) {
									$lesson_access = get_post_meta( $lesson->ID, 'pmpro_courses_bypass_restriction', true );
									?>
									<li id="pmpro_courses-lesson-<?php echo intval( $lesson->ID ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item' ) ); ?>">
										<?php
											// Only add link to single section page if current user has access.
											if ( ! empty( $hasaccess ) || ! empty( $lesson_access ) ) { ?>
												<a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item-link' ) ); ?>" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
												<?php
											}
										?>
										<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-list-item-title' ) ); ?>">
											<?php echo esc_html( $lesson->post_title ); ?>
										</span>
										<?php if ( $lesson_access ) { ?>
											<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-success' ) ); ?>">
												<?php esc_html_e( 'Free', 'pmpro-courses' ); ?>
											</span>
										<?php } ?>
										<?php
											if ( is_user_logged_in() && ! empty( $hasaccess ) ) {
												// Get the status of this lesson.
												$lesson_completed = PMPro_Courses_User_Progress::get_user_lesson_status( $lesson->ID, get_current_user_id() );
												if ( $lesson_completed ) { ?>
													<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-lesson-status pmpro_courses-lesson-status-complete' ) ); ?>">
														<svg class="<?php echo( esc_attr( pmpro_get_element_class( 'pmpro_courses-feather-icon pmpro_courses-feather-icon-complete' ) ) ); ?>" aria-hidden="true">
															<use href="<?php echo esc_url( PMPRO_COURSES_URL . 'images/feather-sprite.svg#check-circle' ); ?>"></use>
														</svg>
														<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-lesson-status-label' ) ); ?>">
															<?php esc_html_e( 'Complete', 'pmpro-courses' ); ?>
														</span>
													</span>
												<?php } else { ?>
													<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-lesson-status pmpro_courses-lesson-status-incomplete' ) ); ?>">
														<svg class="<?php echo( esc_attr( pmpro_get_element_class( 'pmpro_courses-feather-icon pmpro_courses-feather-icon-incomplete' ) ) ); ?>" aria-hidden="true">
															<use href="<?php echo esc_url( PMPRO_COURSES_URL . 'images/feather-sprite.svg#circle' ); ?>"></use>
														</svg>
														<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_courses-lesson-status-label' ) ); ?>">
															<?php esc_html_e( 'Incomplete', 'pmpro-courses' ); ?>
														</span>
													</span>
													<?php
												}
											}
										?>
										<?php 
											// Only add link to single lesson page if current user has access.
											if ( ! empty( $hasaccess ) || ! empty( $lesson_access ) ) { ?>
												</a>
											<?php
											}
										?>
									</li>
									<?php
								}
							?>
							</ol>
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
					<?php
				}
			?>
		</div> <!-- end pmpro_courses-course-outline -->
	</div> <!-- end pmpro-courses -->
	<?php

	$temp_content = ob_get_contents();
	ob_end_clean();

	/**
	 * Filter to allow custom code to modify the structure of the frontend courses list.
	 * 
	 */
	$lessons_html = apply_filters( 'pmpro_courses_get_lessons_html', $temp_content, $course_id, $sections );

	return $lessons_html;
}


/**
 * Get the lessons dropdown HTML with all PMPro lessons that are "available"
 * This is used for the the lesson settings.
 * 
 * @since TBD
 *
 */
function pmpro_courses_lessons_settings( $exclude_lessons = array(), $parent_id = 0 ) {
	// Get all available lessons for the dropdown.
	// Lessons are 1:1 with courses, so only lessons with no parent (post_parent = 0)
	// are eligible. Lessons already assigned to any section of the current course
	// are passed in via $exclude_lessons and excluded here too.
	$all_lessons = get_posts(array(
		'post_type'      => 'pmpro_lesson',
		'posts_per_page' => 99,
		'post_status'    => 'any',
		'exclude'        => $exclude_lessons,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'post_parent'    => 0,
	));

	// Build lessons options HTML
	$lessons_options = '<option value="0">' . esc_html__( 'Select a lesson...', 'pmpro-courses' ) . '</option>';
	
	if ( empty( $all_lessons ) ) {
		$lessons_options = false;
		return $lessons_options;
	}

	foreach ($all_lessons as $lesson) {
		$lessons_options .= sprintf(
			'<option value="%d">%s (#%d)</option>',
			intval( $lesson->ID ),
			esc_html( wp_html_excerpt( $lesson->post_title, 50, '...' ) ),
			intval( $lesson->ID )
		);
	}

	return $lessons_options;
}

/**
 * Get a unique rewrite slug for our CPTs.
 */
function pmpro_courses_unique_rewrite_slug( $slug ) {
	global $wp_post_types;

	$new_slug = $slug;
	$suffix = 0;
	do	{
		$check_for_collision = false;
		foreach( $wp_post_types as $post_type ) {
			// Make sure rewrite values are there.
			if ( empty( $post_type->rewrite ) ) {
				continue;
			}
			if ( empty( $post_type->rewrite['slug'] ) ) {
				continue;
			}
			
			// Is our slug already taken?
			if ( ! empty( $suffix ) ) {
				$new_slug = $slug . '-' . $suffix;
			} else {
				$new_slug = $slug;
			}
			if ( $post_type->rewrite['slug'] === $new_slug ) {
				$suffix = max( 2, $suffix + 1 );	// Start at 2. Increment suffix.
				$check_for_collision = true;		// Check again.
				break;
			}
		}
	} while( $check_for_collision );

	return $new_slug;
}

/**
 * Replace the SQL query where clause string to sort adjacent posts by menu_order instead of post_date.
 *
 * @param string $sql The SQL query string.
 * @return string The modified SQL query string.
 * @since 1.2.7
 */
function pmpro_courses_adjacent_post_where( $sql ) {
	// Bail if not a main query or not a PMPro Lesson post type.
	if ( ! is_main_query() || get_post_type() !== 'pmpro_lesson' ) {
		return $sql;
	}

	//get current post
	$the_post = get_post( get_the_ID() );
	$patterns = array( '/post_date/', '/\'[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\'/' );
	$replacements = array( 'menu_order', $the_post->menu_order );

	// Replace post_date with menu_order
	$sql = preg_replace( $patterns, $replacements, $sql );
	//Ensure the query fetches only lessons that are children of the same course.
	return $sql . ' AND p.post_parent = ' . absint( $the_post->post_parent );
  }
  add_filter( 'get_next_post_where', 'pmpro_courses_adjacent_post_where' );
  add_filter( 'get_previous_post_where', 'pmpro_courses_adjacent_post_where' );

  /**
   * Replace the SQL query order by string to sort adjacent posts by menu_order instead of post_date.
   *
   * @param string $sql The SQL query string.
   * @return string The modified SQL query string.
   * @since 1.2.7
   */
  function pmpro_courses_adjacent_post_sort( $sql ) {
	// Bail if not a main query or not a PMPro Lesson post type.
	if ( !is_main_query() || get_post_type() !== 'pmpro_lesson' ) {
		return $sql;
	}

	$pattern = '/post_date/';
	$replacement = 'menu_order';
	// Replace post_date with menu_order
	return preg_replace( $pattern, $replacement, $sql );
  }
add_filter( 'get_next_post_sort', 'pmpro_courses_adjacent_post_sort' );
add_filter( 'get_previous_post_sort', 'pmpro_courses_adjacent_post_sort' );

/**
 * Get the HTML section.
 *
 * @param array|null $section              The section data array.
 * @param array      $all_assigned_lessons All lesson IDs already assigned to any section
 *                                         of the current course. Used to exclude them from
 *                                         every section's "Add Lesson" dropdown so that a
 *                                         lesson cannot be added twice within the same course.
 * @return void
 */
function pmpro_courses_get_sections_html( $section = null, $all_assigned_lessons = array() ) {
	include( plugin_dir_path( __FILE__ ) . 'adminpages/course-outline/section-settings.php' );
}

/**
 * Returns the post_type ID's that belong to the level ID's.
 * This is used for getting the restricted courses,lessons,groups etc for each module.
 *
 * @since TBD
 * 
 * @param array|int $level_ids The membership level ID or array of membership level IDs to get post IDs for.
 * @param string $post_type The post type to check for (e.g. 'course', 'lesson', etc).
 * @return array An array of post IDs that belong to the given level ID(s) and post type.
 */
function pmpro_courses_get_post_ids_from_levels( $level_ids, $post_type ) {
	global $wpdb;

	// In case a level object was passed in.
	if ( is_object( $level_ids ) ) {
		$level_ids = $level_ids->ID;
	}

	// Make sure we have an array of ids.
	if ( ! is_array( $level_ids ) ) {
		$level_ids = array( $level_ids );
	}

	if ( empty( $level_ids ) ) {
		return array();
	}

	$levels = implode( ', ', array_fill( 0, count( $level_ids ), '%d' ) );
	$sql = $wpdb->prepare(
		"SELECT mp.page_id
		FROM {$wpdb->pmpro_memberships_pages} mp
		LEFT JOIN {$wpdb->posts} p ON mp.page_id = p.ID
		WHERE mp.membership_id IN ({$levels})
		AND p.post_type = %s
		AND p.post_status = 'publish'
		GROUP BY mp.page_id",
		$post_type
	);

	return $wpdb->get_col( $sql );
}