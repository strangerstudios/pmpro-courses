/**
 * Admin JS for PMPro Courses.
 * This is loaded on the Edit Course page and the Courses Settings page.
 */

/**
 * Remove a lesson from the lesson table for a course.
 *
 * @since TBD
 */
function pmpro_courses_remove_lesson(lesson_id) {
	const $row = jQuery('tr[data-lesson_id="' + lesson_id + '"]');
	const $table = $row.closest('table');
	var lesson_text = $row.find('a').first().text().trim();

	if (window.confirm('Are you sure you want to remove the lesson: ' + lesson_text + '?')) {
		$row.remove();
	}

	// If no lesson rows left, add "No Lessons" default row.
	if ($table.find('tr[data-lesson_id]').length === 0) {
		$table.find('tbody').html(
			'<tr class="pmpro-courses-no-lessons"><td colspan="3">No Lessons Added</td></tr>'
		);
	}

	if (lesson_text) {
		// Adjust the lesson text to match the existing logic of not showing too long of a title.
		lesson_text = lesson_text.replace(/\s*\(#\d+\)$/, '').trim();
		if (lesson_text.length > 10) {
			lesson_text = lesson_text.substring(0, 50) + '...';
		}
		lesson_text += ' (#' + lesson_id + ')';

		jQuery('.pmpro_courses_lessons_select').each(function () {
			const $sel = jQuery(this);
			const option = new Option(lesson_text, lesson_id, false, false);
			$sel.append(option);
			$sel.trigger('change.select2');
		});
	}
}

/**
 * Update the Course Lessons table using AJAX.
 * This does not save the data, just updates the DOM.
 *
 * AJAX is needed, because we need to get the WP_POST object for the lesson when building the table row.
 *
 * @since TBD
 */
function pmpro_courses_update_post(button_element) {
	jQuery(button_element).attr('disabled', 'true');

	var button = jQuery(button_element);
	var section = button.closest('.pmpro_courses_lessons-section');
	var lesson_id = section.find('.pmpro_courses_lessons_select').val();
	var section_id = section.data('section-id');

	var data = {
		action:    'pmpro_courses_update_course',
		course_id: pmpro_courses.course_id,
		lesson_id: lesson_id,
		section_id: section_id,
		nonce:     pmpro_courses.nonce
	};

	jQuery.ajax({
		url:      ajaxurl,
		type:     'POST',
		dataType: 'html',
		data:     data,
		error: function () {
			alert('Error adding lesson to course [1]');
			button.html('Add Lesson');
			button.removeAttr('disabled');
		},
		success: function (responseHTML) {
			if (responseHTML === 'error') {
				alert('Error adding lesson to course [2]');
				button.html('Add Lesson');
				button.removeAttr('disabled');
			} else {
				const tbody = section.find('.pmpro_courses_lesson_table tbody');
				const valToRemove = String(lesson_id);
				const $allSelects = jQuery('.pmpro_courses_lessons_select');

				// Remove "No Lessons Added" placeholder row.
				tbody.find('.pmpro-courses-no-lessons').remove();

				// Loop through all selects and remove the lesson value.
				$allSelects.each(function () {
					const $sel = jQuery(this);

					if (String($sel.val()) === valToRemove) {
						$sel.val(null).trigger('change');
					}

					$sel.find('option[value="' + valToRemove + '"]').remove();
					$sel.trigger('change.select2');
				});

				// Append the new row HTML.
				tbody.append(responseHTML);

				jQuery('#pmpro_courses_order').val('');
				button.html('Add Lesson');
				button.removeAttr('disabled');
			}
		}
	});
}

jQuery(document).on('click', '.pmpro_courses_create_lesson', function () {
	pmpro_courses_create_lesson(this);
});

// Callback to create a lesson.
function pmpro_courses_create_lesson(buttonEl) {
	const $btn = jQuery(buttonEl);
	const $section = $btn.closest('.pmpro_courses_lessons-section');
	const section_id = $section.data('section-id');
	const $title = $section.find('.pmpro_courses_new_lesson_title');
	const title = ($title.val() || '').trim();

	if (!title) {
		alert('Please enter a lesson title.');
		return;
	}

	$btn.prop('disabled', true).text('Creating…');

	var data = {
		action:    'pmpro_courses_create_lesson',
		course_id: pmpro_courses.course_id,
		section_id: section_id,
		title:     title,
		nonce:     pmpro_courses.nonce
	};

	jQuery.ajax({
		url:      ajaxurl,
		type:     'POST',
		dataType: 'html',
		data:     data,
		error: function () {
			alert('Error creating lesson.');
			$btn.prop('disabled', false).text('Create Lesson');
		},
		success: function (responseHTML) {
			if (responseHTML === 'error') {
				$btn.prop('disabled', false).text('Create Lesson');
				return;
			}

			const tbody = $section.find('.pmpro_courses_lesson_table tbody');

			// Remove "No Lessons Added" placeholder if present.
			tbody.find('.pmpro-courses-no-lessons').remove();

			// Append the new row HTML returned by PHP.
			tbody.append(responseHTML);

			// Clear the input and restore the button.
			$title.val('');
			$btn.prop('disabled', false).text('Create Lesson');

			// Flag that a change has been made.
			pmpro_courses_made_a_change();
		}
	});
}

/**
 * Toggle the additional settings for a module.
 */
function pmpro_courses_toggle_module_settings(module) {
	var $module_tr = jQuery('tr.pmpro-courses-' + module);
	var $module_toggle = $module_tr.find('input[name="pmpro_courses_modules[]"]');
	var $module_settings = $module_tr.find('.pmpro-courses-module-settings');

	if ($module_toggle.is(':checked')) {
		$module_settings.show();
	} else {
		$module_settings.hide();
	}
}

function pmpro_courses_setup() {
	// Find all course tables and make them sortable on page load.
	pmpro_courses_select2();
	pmpro_courses_make_table_sortable('.pmpro_courses_lesson_table');

	// Editing a course.
	if (pmpro_courses.editing_course) {
		jQuery('#pmpro_courses_order').on('keypress', function (e) {
			if (e.which === 13) {
				pmpro_courses_update_post();
				return false;
			}
		});
	}

	// Courses Settings page.
	if (pmpro_courses.on_settings_page) {
		var $module_settings = jQuery('.pmpro-courses-module-settings');
		$module_settings.each(function () {
			var $this = jQuery(this);
			var module_name = $this.attr('data-module');
			var $module_trigger = jQuery('input[name="pmpro_courses_modules[]"][value="' + module_name + '"]');

			$module_trigger.on('click change', function () {
				pmpro_courses_toggle_module_settings(module_name);
			});

			pmpro_courses_toggle_module_settings(module_name);
		});
	}
}

// Make select2 elements.
function pmpro_courses_select2() {
	jQuery('select.pmpro_courses_lessons_select').select2({
		width: '100%'
	});
}

function pmpro_courses_make_table_sortable(tableSelector) {
	const $ = jQuery;
	const $table = $(tableSelector);
	const $tbody = $table.find('tbody');

	if (!$tbody.length) {
		return;
	}

	// Core-style helper: preserve column widths while dragging.
	const fixHelper = function (e, ui) {
		ui.children().each(function () {
			$(this).width($(this).width());
		});
		return ui;
	};

	$tbody.sortable({
		items: '> tr',
		handle: '.pmpro-sort-handle', // matches <td class="pmpro-lesson-order pmpro-sort-handle">
		axis: 'y',
		cursor: 'move',
		tolerance: 'pointer',
		helper: fixHelper,
		placeholder: 'pmpro-row-placeholder',
		forcePlaceholderSize: true,
		start: function (e, ui) {
			ui.placeholder.height(ui.item.height());
		}
	}).disableSelection();
}

/**
 * Mark that the user has made a change to the course layout.
 */
function pmpro_courses_made_a_change() {
	window.onbeforeunload = function () {
		return true;
	};
	jQuery('#pmpro_courses_savesettings').prop('disabled', false);
}

/**
 * Clear the change flag (e.g. on save).
 */
function pmpro_courses_clear_change_flag() {
	window.onbeforeunload = null;
}

// Function to prep click events (delegated so we only need to run once).
function pmpro_courses_prep_click_events() {

	// Prevent section toggle when clicking on the section name input.
	// Must bind directly (not delegated) so stopPropagation runs before the button's handler.
	jQuery('.pmpro_courses_lessons-section .pmpro_section-toggle-button input[type="text"]').off('click.pmpro_courses').on('click.pmpro_courses', function (e) {
		e.stopPropagation();
	});

	// Add lesson to a section.
	jQuery(document)
		.off('click', '.pmpro_courses_save_lesson')
		.on('click', '.pmpro_courses_save_lesson', function () {
			if (jQuery(this).attr('disabled') !== 'true') {
				jQuery(this).html(pmpro_courses.adding);
				pmpro_courses_update_post(jQuery(this));
				pmpro_courses_made_a_change();
			}
		});

	// Add a new empty section.
	jQuery(document)
		.off('click', '#pmpro_courses_add_section')
		.on('click', '#pmpro_courses_add_section', function (event) {
			event.preventDefault();

			// Find the highest data-section-id in the DOM and increment it by 1.
			var max_id = 0;
			jQuery('.pmpro_courses_lessons-section').each(function () {
				var id = parseInt(jQuery(this).attr('data-section-id'), 10) || 0;
				if (id > max_id) {
					max_id = id;
				}
			});
			var new_id = max_id + 1;

			// Insert the new section HTML, replacing the data-section-id.
			var new_section_html = pmpro_courses.empty_lesson_section_html.replace(
				/data-section-id="\d*"/,
				'data-section-id="' + new_id + '"'
			);

			jQuery('#pmpro_courses_add_section').parent('p').before(new_section_html);

			// Grab the just-inserted section and update fields.
			var inserted_section = jQuery('.pmpro_courses_lessons-section[data-section-id="' + new_id + '"]');
			inserted_section.find('input[name="pmpro_course_lessons_section_id[]"]').val(new_id);
			inserted_section.find('label[for^="pmpro_course_lessons_section_name_"]').attr('for', 'pmpro_course_lessons_section_name_' + new_id);
			inserted_section.find('input[id^="pmpro_course_lessons_section_name_"]').attr('id', 'pmpro_course_lessons_section_name_' + new_id);
			inserted_section.find('table[id^="pmpro_courses_table_"]').attr('id', 'pmpro_courses_table_' + new_id);
			inserted_section.find('label[for^="pmpro_courses_post_"]').attr('for', 'pmpro_courses_post_' + new_id);
			inserted_section.find('select[id^="pmpro_courses_post_"]').attr('id', 'pmpro_courses_post_' + new_id).attr('data-select2-id', 'pmpro_courses_post_' + new_id);
			inserted_section.find('.pmpro_courses_save_lesson').attr('id', 'pmpro_courses_save_' + new_id).attr('data-section-id', new_id);
			inserted_section.find('label[for^="pmpro_courses_new_lesson_title_"]').attr('for', 'pmpro_courses_new_lesson_title_' + new_id);
			inserted_section.find('input[id^="pmpro_courses_new_lesson_title_"]').attr('id', 'pmpro_courses_new_lesson_title_' + new_id);
			inserted_section.find('.pmpro_courses_create_lesson').attr('data-section-id', new_id);

			// Make the table sortable and select2.
			pmpro_courses_select2();
			pmpro_courses_make_table_sortable(inserted_section.find('.pmpro_courses_lesson_table'));

			// Prevent section toggle when clicking on the new section name input.
			inserted_section.find('.pmpro_section-toggle-button input[type="text"]').on('click.pmpro_courses', function (e) {
				e.stopPropagation();
			});

			// Bind toggle handler to the new section (PMPro core only binds to existing sections)
			inserted_section.find('button.pmpro_section-toggle-button').on('click', function (event) {
				event.preventDefault();
				var thebutton = jQuery(this);
				var buttonicon = thebutton.children('.dashicons');
				var section = thebutton.closest('.pmpro_section');
				var sectioninside = section.children('.pmpro_section_inside');
				if (buttonicon.hasClass('dashicons-arrow-down-alt2')) {
					sectioninside.show();
					buttonicon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
					section.attr('data-visibility', 'shown');
					thebutton.attr('aria-expanded', 'true');
				} else {
					sectioninside.hide();
					buttonicon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
					section.attr('data-visibility', 'hidden');
					thebutton.attr('aria-expanded', 'false');
				}
			});

			// Auto-open the new section
			inserted_section.find('.pmpro_section_inside').show();
			inserted_section.find('button.pmpro_section-toggle-button .dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
			inserted_section.attr('data-visibility', 'shown');
			inserted_section.find('button.pmpro_section-toggle-button').attr('aria-expanded', 'true');

			pmpro_courses_made_a_change();

			// Focus on the new section name input.
			jQuery('#pmpro_course_lessons_section_name_' + new_id).focus().select();
		});

	// Delete a specific section.
	jQuery(document)
		.off('click', '.pmpro-has-icon-trash')
		.on('click', '.pmpro-has-icon-trash', function () {
			var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
			var section_name = the_section.find('input[name="pmpro_course_lessons_section_name[]"]').val();

			// Cannot delete the section if there's only one left.
			if (jQuery('.pmpro_courses_lessons-section').length <= 1) {
				alert('You cannot delete this section. A minimum of one section is required.');
				return;
			}

			var answer;
			if (section_name && section_name.length > 0) {
				answer = window.confirm('Delete the "' + section_name + '" section?');
			} else {
				answer = window.confirm('Delete this section?');
			}

			if (answer) {
				the_section.remove();
				pmpro_courses_made_a_change();
			}
		});

	// Move section up.
	jQuery(document)
		.off('click', '.pmpro_courses_lessons-section-sort .pmpro_section-sort-button-move-up')
		.on('click', '.pmpro_courses_lessons-section-sort .pmpro_section-sort-button-move-up', function () {
			var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
			var prev_section = the_section.prev('.pmpro_courses_lessons-section');

			if (prev_section.length > 0) {
				the_section.insertBefore(prev_section);
				pmpro_courses_made_a_change();
			}
		});

	// Move section down.
	jQuery(document)
		.off('click', '.pmpro_courses_lessons-section-sort .pmpro_section-sort-button-move-down')
		.on('click', '.pmpro_courses_lessons-section-sort .pmpro_section-sort-button-move-down', function () {
			var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
			var next_section = the_section.next('.pmpro_courses_lessons-section');

			if (next_section.length > 0) {
				the_section.insertAfter(next_section);
				pmpro_courses_made_a_change();
			}
		});
}

// Once document is loaded, let's load PMPro Courses JS.
jQuery(document).ready(function () {
	pmpro_courses_setup();
	pmpro_courses_prep_click_events();
});

jQuery(function () {
	if (typeof wp === 'undefined' || !wp.data || !wp.data.select) {
		return;
	}

	const { select } = wp.data;
	let wasSaving = false;

	wp.data.subscribe(function () {
		const isSaving = select('core/editor').isSavingPost();
		const isAutosaving = select('core/editor').isAutosavingPost();

		// Detect transition: was saving -> done saving (and not an autosave)
		if (wasSaving && !isSaving && !isAutosaving) {
			pmpro_courses_clear_change_flag();
		}

		wasSaving = isSaving;
	});
});
