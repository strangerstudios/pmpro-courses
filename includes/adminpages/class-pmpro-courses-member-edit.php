<?php
/**
 * Class for Edit Member Panel.
 */
class PMPro_Courses_Member_Edit_Panel extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug        = 'pmpro-courses';
		$this->title       = __( 'Courses', 'pmpro-courses' );
	}

	/**
	 * Display a table of lesson content.
	 */
	protected function display_panel_contents() {
		$user = self::get_user();
		$user_id = (int) $user->ID;
		// Get a list of courses that the member has access to OR has completed lessons.
		$member_courses = pmpro_courses_get_courses( 200, $user_id );
		?>
		<table class="wp-list-table widefat striped fixed">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Course', 'pmpro-courses' ); ?></th>
					<th><?php esc_html_e( 'First Seen', 'pmpro-courses' ); ?></th>
					<th><?php esc_html_e( 'Last Seen', 'pmpro-courses' ); ?></th>
					<th><?php esc_html_e( 'Progress', 'pmpro-courses' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ( ! empty( $member_courses ) ) {
				foreach( $member_courses as $course ) {
					// Enrollment date would be the time of completing the 'oldest' lesson for the specific course.
					$lessons = PMPro_Courses_User_Progress::get_completed_lessons_for_a_course( $course->ID, $user_id );
					$progress = PMPro_Courses_User_Progress::get_course_progress_for_user( $course->ID, $user_id );

					$first_completed_lesson = ! empty( $lessons ) ? reset( $lessons ) : null;

					// If the course has more than one lesson or the progress of the course is 100 (i.e. 1 lesson in the course)
					if ( $progress === 100 || count( $lessons ) > 1 ) {
						$last_completed_lesson  = ! empty( $lessons ) ? end( $lessons ) : null;
					} else {
						$last_completed_lesson = null;
					}
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" target="_blank">
								<?php echo esc_html( $course->post_title ); ?>
							</a>
						</td>
						<td>
							<?php
								if ( empty( $first_completed_lesson ) && empty( $first_completed_lesson->completed_at ) ) {
									echo esc_html_x( '&#8212;', 'A dash is shown when there is no first seen date.', 'pmpro-courses' );
								} else {
									$first_completed_lesson_timestamp = strtotime( $first_completed_lesson->completed_at );
									echo esc_html(
										sprintf(
											// translators: %1$s is the date and %2$s is the time.
											__( '%1$s at %2$s', 'pmpro-courses' ),
											esc_html( date_i18n( get_option( 'date_format' ), $first_completed_lesson_timestamp ) ),
											esc_html( date_i18n( get_option( 'time_format' ), $first_completed_lesson_timestamp ) )
										)
									);
								}
							?>
						</td>
						<td>
							<?php
								if ( empty( $last_completed_lesson ) && empty( $last_completed_lesson->completed_at ) ) {
									echo esc_html_x( '&#8212;', 'A dash is shown when there is no last seen date.', 'pmpro-courses' );
								} else {
									$last_completed_lesson_timestamp = strtotime( $last_completed_lesson->completed_at );
									echo esc_html(
										sprintf(
											// translators: %1$s is the date and %2$s is the time.
											__( '%1$s at %2$s', 'pmpro-courses' ),
											esc_html( date_i18n( get_option( 'date_format' ), $last_completed_lesson_timestamp ) ),
											esc_html( date_i18n( get_option( 'time_format' ), $last_completed_lesson_timestamp ) )
										)
									);
								}
							?>
						</td>
						<td><?php echo esc_html( $progress ); ?>%</td>
					</tr>
					<?php
				}
			}
			?>
		</table>
	<?php
	}
}
