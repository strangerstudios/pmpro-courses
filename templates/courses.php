<?php
/**
 * Displays a list of courses
 *
 */
if ( ! empty( $courses ) ) { ?>
	<div class="pmpro_courses pmpro_courses-courses">
		<h4 class="pmpro_courses-title"><?php _e('Courses', 'pmpro-courses'); ?></h4>
		<ul class="pmpro_courses-list">
			<?php
				foreach( $courses as $course ) { ?>
					<li id="pmpro_courses-course-<?php echo $course['id']; ?>" class="pmpro_courses-list-item">
						<a href="<?php echo $course['permalink']; ?>" class="pmpro_courses-list-link">
							<div class="pmpro_courses-list-item-title"><?php echo $course['title']; ?></div>
							<?php if ( $course['lessons'] > 0 ) { ?>
								<div class="pmpro_courses-list-item-count">
									<?php printf( _n( '%s Lesson', '%s Lessons', $course['lessons'], 'pmpro-courses' ), number_format_i18n( $course['lessons'] ) ); ?>
								</div>
							<?php } ?>
						</a>
					</li>
					<?php
				}
			?>
		</ul> <!-- end pmpro_courses-list -->
	</div> <!-- end pmpro_courses -->
	<?php
	}
?>
