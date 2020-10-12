<div class='pmpro_courses_shortcode_container'>
	<ul>
		<?php
		if( !empty( $courses ) ){ 
			foreach( $courses as $course ){ 

			echo '<li>';
				if( $course['featured'] ) { 
					echo "<div class='pmproc_featured_image'><img src='".$course['featured']."' title='".$course['title']."'/></div>";
				}
				echo "<div class='pmproc_title'><a href='".$course['permalink']."'>".$course['title']."</a></div>";
				echo "<div class='pmproc_description'>".$course['excerpt']."</div>";
				echo "<div class='pmproc_lessons'>".$course['lessons']." ".__('Lessons', 'pmpro-courses')."</div>";
				if( apply_filters( 'pmpro_courses_show_view_course', true ) ){
					echo "<a href='".$course['permalink']."'>".apply_filters( 'pmpro_courses_view_course_text', __('View Course', 'pmpro-courses') )."</a>";
				}
			echo '</li>';
			} 
		} 
		?>
	</ul>
</div>
