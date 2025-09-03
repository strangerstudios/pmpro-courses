<?php

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
			'post_type' => 'pmpro_lesson'
		) );
		
		if ( ! empty( $lessons ) ) {
			$lesson_table_html = pmpro_courses_get_lessons_table_html( $lessons, $section_id );
		}

	}
}

// Defaults, get all PMPro Lessons posts that are published.
$lessons_options = pmpro_courses_lessons_settings();
?>
<div class="pmpro_courses_lessons-section" data-section-id="<?php echo esc_attr( $section_id ); ?>">
	<div class="pmpro_courses_lessons-section-header">
		<div class="pmpro_courses_lessons-section-buttons">

			<button type="button" aria-disabled="false" class="pmpro_courses_lessons-section-buttons-button pmpro_courses_lessons-section-buttons-button-move-up" aria-label="<?php echo esc_attr__('Move up', 'pmpro-courses'); ?>">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</button>
			<span class="pmpro_courses_lessons-section-buttons-description"><?php echo esc_html__('Move Section Up', 'pmpro-courses'); ?></span>

			<button type="button" aria-disabled="false" class="pmpro_courses_lessons-section-buttons-button pmpro_courses_lessons-section-buttons-button-move-down" aria-label="<?php echo esc_attr__('Move down', 'pmpro-courses'); ?>">
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<span class="pmpro_courses_lessons-section-buttons-description"><?php echo esc_html__('Move Section Down', 'pmpro-courses'); ?></span>

			<button type="button" aria-disabled="false" class="pmpro_courses_lessons-section-buttons-button delete-section-btn" aria-label="<?php echo esc_attr__('Delete section', 'pmpro-courses'); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
		<h3>
			<label for="pmpro_course_lessons_section_name_<?php echo esc_attr( $section_id ); ?>">
				<?php echo esc_html__('Section Name', 'pmpro-courses'); ?>
				<input type="text" name="pmpro_course_lessons_section_name[]" id="pmpro_course_lessons_section_name_<?php echo esc_attr( $section_id ); ?>" placeholder="<?php echo esc_attr__('Section Name', 'pmpro-courses'); ?>" value="<?php echo esc_attr( $section_name ); ?>" />
				<input type="hidden" name="pmpro_course_lessons_section_id[]" value="<?php echo esc_attr( $section_id ); ?>" />
			</label>
		</h3>
		<button type="button" aria-disabled="false" class="pmpro_courses_lessons-section-buttons-button pmpro_courses_lessons-section-buttons-button-toggle-section" aria-label="<?php echo esc_attr__('Expand and Edit Section', 'pmpro-courses'); ?>">
			<span class="dashicons dashicons-arrow-down"></span>
		</button>
		<span class="pmpro_courses_lessons-section-buttons-description"><?php echo esc_html__('Expand and Edit Section', 'pmpro-courses'); ?></span>
	</div>
	<div class="pmpro_course_lesson-inside" style="display: none;">
		<div class="pmpro_course_lesson-field-settings">
			<table id="pmpro_courses_table_<?php echo esc_attr( $section_id ); ?>" class="wp-list-table widefat striped pmpro-metabox-items pmpro_courses_lesson_table">
				<thead>
					<tr>
						<th><?php echo esc_html__('Order', 'pmpro-courses'); ?></th>
						<th width="50%"><?php echo esc_html__('Title', 'pmpro-courses'); ?></th>
						<th width="20%"><?php echo esc_html__('Actions', 'pmpro-courses'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php echo isset($lesson_table_html) ? $lesson_table_html : '<tr class="no-lessons"><td colspan="3" style="text-align:center;">' . esc_html__( 'No Lessons Added', 'pmpro-courses' ) . '</td></tr>'; ?>
				</tbody>
			</table>

			<h3><?php echo esc_html__('Add Lessons', 'pmpro-courses'); ?></h3>
			<table id="newmeta" class="wp-list-table pmpro-metabox-items">
				<tbody>
					<tr>
						<td>
							<label for="pmpro_courses_post"><?php echo esc_html__('Lesson', 'pmpro-courses'); ?></label>
							<select id="pmpro_courses_post" class="pmpro_courses_lessons_select" name="pmpro_courses_post">
								<?php echo isset($lessons_options) ? $lessons_options : ''; ?>
							</select>
						</td>
						<td width="20%">
							<a class="button button-primary pmpro_courses_save_lesson" id="pmpro_courses_save" data-section-id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html__('Add Lesson', 'pmpro-courses'); ?></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>