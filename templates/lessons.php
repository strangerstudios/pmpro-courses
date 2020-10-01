<?php $lessons = pmpro_courses_get_lessons( $post->ID ); ?>

<h3><?php _e('Lessons', 'pmpro-courses'); ?></h3>
<hr>
<div class='pmpro_courses_lessons_container'>

	<?php
		if( !empty( $lessons ) ){
			foreach( $lessons as $lesson ){
				?>
				<div class='pmpro_courses_lesson' id='pmpro_courses_lesson_<?php echo $lesson['id']; ?>'>
					<div class='pmpro_courses_lesson_title'><a href='<?php echo $lesson['permalink']; ?>'><?php echo $lesson['title']; ?></a></div>
					<div class='pmpro_courses_lesson_content'><?php echo $lesson['content']; ?></div>
				</div>

				<?php
				if( is_user_logged_in() ){
					echo pmproc_complete_button( $lesson['id'], $post->ID );
				}
			}
		} else {
			_e('No lessons found for this course', 'pmpro-courses');
		}
	?>
</div>