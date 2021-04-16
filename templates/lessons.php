<?php
/**
 * Displays a list of lessons
 */
$lessons = pmpro_courses_get_lessons( $post->ID );
if ( ! empty( $lessons ) ) { ?>
	<div class="pmpro_courses pmpro_courses-lessons">
		<h3 class="pmpro_courses-title"><?php _e('Lessons', 'pmpro-courses'); ?></h3>
		<ol class="pmpro_courses-list">
			<?php
				foreach( $lessons as $lesson ) { ?>
					<li id="pmpro_courses-lesson-<?php echo intval( $lesson->ID ); ?>" class="pmpro_courses-list-item">
						<a class="pmpro_courses-list-item-link" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
							<div class="pmpro_courses-list-item-title">
								<?php echo esc_html( $lesson->post_title ); ?>
							</div>
							<?php
								if ( is_user_logged_in() ) {
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
						</a>
					</li>
					<?php
				}
			?>
		</ol> <!-- end pmpro_courses-list -->
	</div> <!-- end pmpro_courses -->
	<?php
	}
?>
