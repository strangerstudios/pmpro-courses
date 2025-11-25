<?php

global $post;

if ( empty( $section ) ) {
	$section_name = '';
	$section_id = 1;
} else {
	$section_name = sanitize_text_field( $section['section_name'] );
	$section_id = (int) $section['section_id'];

	if ( ! empty( $section['lessons']) ) {
		// Get the lessons to build the lesson table.
		$lessons = get_posts( array(
			'post__in' => $section['lessons'],
			'post_type' => 'pmpro_lesson',
			'numberposts' => 99,
			'post_status' => array( 'draft', 'publish' ),
			'orderby' => 'menu_order',
			'order' => 'ASC'
		) );
		
		if ( ! empty( $lessons ) ) {
			$lesson_table_html = pmpro_courses_get_lessons_table_html( $lessons, $section_id );
		}
	}
}

// Defaults, get all PMPro Lessons posts that are available to assign, exclude any that have "another" post parent and exclude already assigned lessons to current course.
$exclude_assigned_lessons = isset( $section['lessons'] ) ? array_map( 'intval', $section['lessons'] ) : array();
$lessons_options = pmpro_courses_lessons_settings( $exclude_assigned_lessons, $post->ID );
?>
<div class="pmpro_section pmpro_courses_lessons-section" data-section-id="<?php echo esc_attr( $section_id ); ?>">
	<div class="pmpro_section_toggle">
		<div class="pmpro_section-sort pmpro_courses_lessons-section-sort">
			<button type="button" aria-disabled="false" class="pmpro_section-sort-button pmpro_section-sort-button-move-up pmpro_courses_lessons-section-buttons-button" aria-label="<?php echo esc_attr__( 'Move up', 'pmpro-courses' ); ?>">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</button>
			<span class="pmpro_section-sort-button-description pmpro_courses_lessons-section-buttons-description">
				<?php echo esc_html__( 'Move Section Up', 'pmpro-courses' ); ?>
			</span>
			<button type="button" aria-disabled="false" class="pmpro_section-sort-button pmpro_section-sort-button-move-down pmpro_courses_lessons-section-buttons-button" aria-label="<?php echo esc_attr__( 'Move down', 'pmpro-courses' ); ?>">
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<span id="pmpro_courses_lessons-section-buttons-description-2" class="pmpro_section-sort-button-description pmpro_courses_lessons-section-buttons-description"
			>
				<?php echo esc_html__( 'Move Section Down', 'pmpro-courses' ); ?>
			</span>
		</div>
		<button class="pmpro_section-toggle-button" aria-expanded="false">
			<span class="dashicons dashicons-arrow-down-alt2"></span>
			<label for="pmpro_course_lessons_section_name_<?php echo esc_attr( $section_id ); ?>">
				<?php esc_html_e( 'Section Name', 'pmpro-courses' ); ?>
				<input type="text" name="pmpro_course_lessons_section_name[]" id="pmpro_course_lessons_section_name_<?php echo esc_attr( $section_id ); ?>" placeholder="<?php echo esc_attr__('Section Name', 'pmpro-courses'); ?>" value="<?php echo esc_attr( $section_name ); ?>" />
				<input type="hidden" name="pmpro_course_lessons_section_id[]" value="<?php echo esc_attr( $section_id ); ?>" />
			</label>
		</button>
	</div>
	<div class="pmpro_section_inside pmpro_course_lesson-inside" style="display: none;">
		<table id="pmpro_courses_table_<?php echo esc_attr( intval( $section_id ) ); ?>" class="wp-list-table widefat striped pmpro_courses_lesson_table">
			<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( 'Title', 'pmpro-courses' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'pmpro-courses' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php echo isset( $lesson_table_html ) ? $lesson_table_html : '<tr class="pmpro-courses-no-lessons"><td colspan="3"><p>' . esc_html__( 'No Lessons Added', 'pmpro-courses' ) . '</p></td></tr>'; ?>
			</tbody>
		</table>
		<table class="wp-list-table widefat striped pmpro_courses_add_lesson_table">
			<thead>
				<tr>
					<th colspan="3"><strong><?php echo esc_html__('Add Lessons to Section', 'pmpro-courses'); ?></strong></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<label for="pmpro_courses_post_<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html_e( 'Add Existing Lessons', 'pmpro-courses' ); ?></label>
					</td>
					<td>
						<select class="pmpro_courses_lessons_select" name="pmpro_courses_post" id="pmpro_courses_post_<?php echo esc_attr( $section_id ); ?>">
							<?php echo isset( $lessons_options ) ? $lessons_options : ''; ?>
						</select>
					</td>
					<td>
						<a class="button button-primary pmpro_courses_save_lesson" id="pmpro_courses_save_<?php echo esc_attr( $section_id ); ?>" data-section-id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html_e( 'Add Lesson', 'pmpro-courses' ); ?></a>
					</td>
				</tr>
				<tr>
					<td>
						<label for="pmpro_courses_new_lesson_title_<?php echo esc_attr( $section_id ); ?>"><?php esc_html_e( 'Create a New Draft Lesson', 'pmpro-courses' ); ?></label>
					</td>
					<td>
						<input type="text" id="pmpro_courses_new_lesson_title_<?php echo esc_attr( $section_id ); ?>" name="pmpro_courses_new_lesson_title" class="pmpro_courses_new_lesson_title" placeholder="<?php esc_attr_e( 'Enter a lesson title', 'pmpro-courses' ); ?>" />
					</td>
					<td>
						<a class="button button-primary pmpro_courses_create_lesson" id="pmpro_courses_create_lesson" data-section-id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html_e( 'Create Lesson', 'pmpro-courses' ); ?></a>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="pmpro_section_actions">
			<button type="button" class="button is-destructive pmpro-has-icon pmpro-has-icon-trash" aria-disabled="false" aria-label="<?php echo esc_attr__('Delete section', 'pmpro-courses'); ?>">
				<?php echo esc_html__('Delete Section', 'pmpro-courses'); ?>
			</button>
		</div> <!-- .pmpro_section_actions -->
	</div> <!-- .pmpro_section_inside -->
</div> <!-- .pmpro_section -->
