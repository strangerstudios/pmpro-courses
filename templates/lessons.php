<?php
/**
 * Displays a list of lessons 
 *
 */
$lessons = pmpro_courses_get_lessons( $post->ID );
if ( ! empty( $lessons ) ) { ?>
	<div class="pmpro_courses pmpro_courses-lessons">
		<h4 class="pmpro_courses-title"><?php _e('Lessons', 'pmpro-courses'); ?></h4>
		<ol class="pmpro_courses-list">
			<?php
				foreach( $lessons as $lesson ) { ?>
					<li id="pmpro_courses-lesson-<?php echo $lesson['id']; ?>" class="pmpro_courses-list-item">
						<div class="pmpro_courses-list-item-title"><a href="<?php echo $lesson['permalink']; ?>"><?php echo $lesson['title']; ?></a></div>
						<?php
							if ( is_user_logged_in() ) {
								echo pmproc_complete_button( $lesson['id'], $post->ID );
							}
						?>
					</li>
					<?php
				}
			?>
		</ol> <!-- end pmpro_courses-list -->
	</div> <!-- end pmpro_courses -->
	<?php
	}
?>
