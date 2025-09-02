
function pmpro_courses_edit_post(post_id, order) {
	jQuery('#pmpro_courses_post').val(post_id).trigger("change");
	jQuery('#pmpro_courses_order').val(order);
	jQuery('#pmpro_courses_order').focus();
	jQuery('#pmpro_courses_save').html('Save');
	location.href = "#pmpro_courses_edit_post";
}

function pmpro_courses_remove_post(post_id) {

	var data = {
		action: 'pmpro_courses_remove_course',
		course: pmpro_courses.course_id,
		lesson: post_id,
		nonce: pmpro_courses.nonce
	}

	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		timeout: 2000,
		dataType: 'html',
		data: data,
		error: function (xml) {
			alert('Error removing lesson [1]');
			//enable save button
			jQuery('#pmpro_courses_save').removeAttr('disabled');
		},
		success: function (responseHTML) {
			if (responseHTML == 'error') {
				alert('Error removing lesson [2]');
				//enable save button
				jQuery('#pmpro_courses_save').removeAttr('disabled');
			} else {
				jQuery('#pmpro_courses_table tbody').html(responseHTML);
			}
		}
	});
}

/**
 * Update the Course Lessons table using AJAX.
 * This does not save the data, just updates the DOM.
 * 
 * @since TBD
 */
function pmpro_courses_update_post(button_element) {

	console.log(button_element + ' clicked');
	jQuery(button_element).attr('disabled', 'true');

	var button = jQuery(button_element);
    var section = button.closest('.pmpro_courses_lessons-section');
	var lesson_id = section.find('.pmpro_courses_lessons_select').val();
	var order = jQuery('#pmpro_courses_order').val();

	var data = {
		action: 'pmpro_courses_update_course',
		course_id: pmpro_courses.course_id,
		lesson_id: lesson_id,
		nonce: pmpro_courses.nonce
	}

	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'html',
		data: data,
		error: function (xml) {
			alert('Error adding lesson to course [1]');
			//enable save button
			jQuery('#pmpro_courses_save').html('Add Lesson');
			jQuery('#pmpro_courses_save').removeAttr('disabled');
		},
		success: function (responseHTML) {
			if (responseHTML == 'error') {
				alert('Error adding lesson to course [2]');
				//enable save button
				jQuery('#pmpro_courses_save').html('Add Lesson');
				jQuery('#pmpro_courses_save').removeAttr('disabled');
			} else {
				// If there is a row with the class ".no-lessons", remove it.
				const tbody = section.find('.pmpro_courses_lesson_table tbody');

				// Remove "No Added Lessons"
				tbody.find('.no-lessons').remove();

				tbody.append(responseHTML);

				// jQuery('#pmpro_courses_post').val(null).trigger('change');
				// jQuery('#pmpro_courses_section').val(null).trigger('change');
				jQuery('#pmpro_courses_order').val('');
				jQuery('#pmpro_courses_save').html('Add Lesson');
				jQuery('#pmpro_courses_save').removeAttr('disabled');
			}
		}
	});
}

/**
 * Toggle the additional settings for a module.
 */
function pmpro_courses_toggle_module_settings(module) {
	$module_tr = jQuery('tr.pmpro-courses-' + module);
	$module_toggle = $module_tr.find('input[name="pmpro_courses_modules[]"]');
	$module_settings = $module_tr.find('.pmpro-courses-module-settings');
	if ($module_toggle.is(':checked')) {
		$module_settings.show();
	} else {
		$module_settings.hide();
	}
}

function pmpro_courses_setup() {

	// Editing a course.
	if (pmpro_courses.editing_course) {
		jQuery('.pmpro_courses_lessons_select').select2({ width: 'elements' });

		jQuery('#pmpro_courses_order').keypress(function (e) {
			if (e.which == 13) {
				pmpro_courses_update_post();
				return false;
			}
		});

	}

	// Courses Settings page.
	if (pmpro_courses.on_settings_page) {
		// Hide module settings.
		$module_settings = jQuery('.pmpro-courses-module-settings');
		$module_settings.each(function () {
			$module_name = $module_settings.attr('data-module');
			$module_trigger = jQuery('input[name="pmpro_courses_modules[]"][value="' + $module_name + '"]');
			$module_trigger.bind('click change', function () {
				pmpro_courses_toggle_module_settings($module_name);
			});
			pmpro_courses_toggle_module_settings($module_name);
		});
	}
}

// Function to prep click events.
function pmpro_courses_prep_click_events() {

	jQuery('#pmpro_courses_add_section').parent('p').prev().find('select').focus().select2({ width: 'elements' });

	// Whenever we make a change, warn the user if they try to navigate away. 
	/// DO we need this.
	function pmpro_courses_made_a_change() {
		window.onbeforeunload = function () {
			return true;
		};
		jQuery('#pmpro_courses_savesettings').prop("disabled", false);
	}

	// Whenever the lesson button is clicked to add a lesson.
	jQuery('.pmpro_courses_save_lesson').off('click').on('click', function () {
		if (jQuery(this).attr('disabled') !== 'true') {
			jQuery(this).html(pmpro_courses.adding);
			pmpro_courses_update_post(jQuery(this));
		}
	});

	// Add a new empty section
	jQuery('#pmpro_courses_add_section').unbind('click').on('click', function (event) {
		event.preventDefault();
		// Find the last section and get its data-section-id, then increment by 1.
		var last_section = jQuery('.pmpro_courses_lessons-section').last();
		var last_id = parseInt(last_section.attr('data-section-id')) || 0;
		var new_id = last_id + 1;

		// Insert the new section HTML, replacing the data-section-id.
		var new_section_html = pmpro_courses.empty_lesson_section_html.replace(/data-section-id="\d*"/, 'data-section-id="' + new_id + '"');
		jQuery('#pmpro_courses_add_section').parent('p').before(new_section_html);
		pmpro_courses_prep_click_events();
		jQuery('#pmpro_courses_add_section').parent('p').prev().find('input').focus().select();
	});

	// Delete a specific section.
	jQuery('.pmpro_courses_lessons-section-buttons-button.delete-section-btn').unbind('click').on('click', function () {
		var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
		var section_name = the_section.find('input[name="pmpro_course_lessons_section_name"]').val();

		// Cannot delete the section if there's only one left.
		if (jQuery('.pmpro_courses_lessons-section').length <= 1) {
			alert('You cannot delete this section. A minimum of one section is required.');
			return;
		}

		var answer;
		if (section_name.length > 0) {
			answer = window.confirm('Delete the "' + section_name + '" section?');
		} else {
			answer = window.confirm('Delete this section?');
		}
		if (answer) {
			the_section.remove();
		}
	});

	// Toggle a specific section.
	jQuery('button.pmpro_courses_lessons-section-buttons-button-toggle-section, div.pmpro_courses_lessons-section-header h3').unbind('click').on('click', function (event) {
		event.preventDefault();

		// Ignore if the text field was clicked.
		if (jQuery(event.target).prop('nodeName') === 'INPUT') {
			return;
		}

		// Find the toggle button and open or close.
		let the_button = jQuery(event.target).parents('.pmpro_courses_lessons-section').find('button.pmpro_courses_lessons-section-buttons-button-toggle-section');
		let button_icon = the_button.children('.dashicons');
		let section_header = the_button.closest('.pmpro_courses_lessons-section-header');
		let section_inside = section_header.siblings('.pmpro_course_lesson-inside');

		if (button_icon.hasClass('dashicons-arrow-up')) {
			// closing
			button_icon.removeClass('dashicons-arrow-up');
			button_icon.addClass('dashicons-arrow-down');
			section_inside.slideUp();
		} else {
			// opening
			button_icon.removeClass('dashicons-arrow-down');
			button_icon.addClass('dashicons-arrow-up');
			section_inside.slideDown();
		}
	});

	// Move section up or down.
	jQuery('.pmpro_courses_lessons-section-buttons-button-move-up').unbind('click').on('click', function (event) {
		var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
		var prev_section = the_section.prev('.pmpro_courses_lessons-section');
		if (prev_section.length > 0) {
			the_section.insertBefore(prev_section);
			// pmpro_courses_made_a_change();
		}
	});

	// Move section down.
	jQuery('.pmpro_courses_lessons-section-buttons-button-move-down').unbind('click').on('click', function (event) {
		var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
		var next_section = the_section.next('.pmpro_courses_lessons-section');
		if (next_section.length > 0) {
			the_section.insertAfter(next_section);
			// pmpro_courses_made_a_change();
		}
	});

}

// Once document is loaded, let's load PMPro Courses JS.
jQuery(document).ready(function() {
	pmpro_courses_setup();
	pmpro_courses_prep_click_events();
});
