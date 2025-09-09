
function pmpro_courses_edit_post(post_id, order) {
	jQuery('#pmpro_courses_post').val(post_id).trigger("change");
	jQuery('#pmpro_courses_order').val(order);
	jQuery('#pmpro_courses_order').focus();
	jQuery('#pmpro_courses_save').html('Save');
	location.href = "#pmpro_courses_edit_post";
}

/**
 * Remove a lesson from the lesson table for a course.
 * 
 * @since TBD
 */
function pmpro_courses_remove_lesson(lesson_id) {

	// Show an alert/confirmation 
	const $row = jQuery('tr[data-lesson_id="' + lesson_id + '"]');
	const $table = $row.closest('table');
	const lesson_text = $row.find('a').first().text().trim();

	if (confirm("Are you sure you want to remove the lesson: " + lesson_text + "?")) {
		// Remove the row
		$row.remove();
	}

	// If no lesson rows left, add "No Lessons" default row
	if ($table.find('tr[data-lesson_id]').length === 0) {
		$table.find('tbody').html(
			'<tr class="no-lessons"><td colspan="3" style="text-align:center;">No Lessons Added</td></tr>'
		);
	}

	// Put the select2 option back to the dropdown when removing.
	if (lesson_text) {
		jQuery('.pmpro_courses_lessons_select').each(function () {
		const $sel = jQuery(this);

		// Add new option node (clone because an <option> can only live in one place)
		const option = new Option(lesson_text, lesson_id, false, false);
		$sel.append(option);

		// Refresh Select2 UI
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
		action: 'pmpro_courses_update_course',
		course_id: pmpro_courses.course_id,
		lesson_id: lesson_id,
		section_id: section_id,
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
			button.html('Add Lesson');
			button.removeAttr('disabled');
		},
		success: function (responseHTML) {
			if (responseHTML == 'error') {
				alert('Error adding lesson to course [2]');
				//enable save button
				button.html('Add Lesson');
				button.removeAttr('disabled');
			} else {
				// If there is a row with the class ".no-lessons", remove it.
				const tbody = section.find('.pmpro_courses_lesson_table tbody');
				const valToRemove = String(lesson_id);
				const $allSelects = jQuery('.pmpro_courses_lessons_select');


				// Remove "No Added Lessons"
				tbody.find('.no-lessons').remove();

				// Loop through all selects and remove the lesson value.
				$allSelects.each(function () {
				const $sel = jQuery(this);

				// If this select currently has the just-added value selected, clear it first
				if (String($sel.val()) === valToRemove) {
					$sel.val(null).trigger('change'); // clears the selection in Select2 UI
				}

				// Remove the option from the DOM so it disappears from the dropdown
				$sel.find('option[value="' + valToRemove + '"]').remove();

				// Tell Select2 to refresh from the underlying <select>
				$sel.trigger('change.select2');
				});

				// Add a row of data.
				tbody.append(responseHTML);

				jQuery('#pmpro_courses_order').val('');
				button.html('Add Lesson');
				button.removeAttr('disabled');
			}
		}
	});
}


// Let's build lesson reordering to move rows around within a specific table.


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

	// Find all course tables and make them sortable on page load.
	pmpro_courses_select2();
	pmpro_courses_make_table_sortable( '.pmpro_courses_lesson_table' );


	// Editing a course.
	if (pmpro_courses.editing_course) {
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

// Make select2 elements
function pmpro_courses_select2() {
    jQuery('select.pmpro_courses_lessons_select').select2({
        width: 'elements'
    });
}

function pmpro_courses_make_table_sortable( $table ) {
	const $tbody = jQuery($table).find('tbody');

	// Make rows draggable; no renumbering or extra work needed.
	$tbody.sortable({
		items: '> tr',
		axis: 'y',
		cursor: 'move',
		tolerance: 'pointer',
		helper: function(e, tr) {
		// Keep column widths while dragging
		const $originals = tr.children();
		const $helper = tr.clone();
		$helper.children().each(function(i) {
			jQuery(this).width($originals.eq(i).width());
		});
		return $helper;
		},
		placeholder: 'pmpro-row-placeholder',
		start: function(e, ui) {
		ui.placeholder.height(ui.item.height());
		},
		update: function() {
		// Nothing to do: inputs move with the row, so $_POST order is correct.
		}
	}).disableSelection();

}

// Function to prep click events.
function pmpro_courses_prep_click_events() {
	
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

		// Find the highest data-section-id in the DOM and increment it by 1.
		var max_id = 0;
		jQuery('.pmpro_courses_lessons-section').each(function () {
			var id = parseInt(jQuery(this).attr('data-section-id')) || 0;
			if (id > max_id) {
				max_id = id;
			}
		});
		var new_id = max_id + 1;

		// Insert the new section HTML, replacing the data-section-id.
		var new_section_html = pmpro_courses.empty_lesson_section_html.replace(/data-section-id="\d*"/, 'data-section-id="' + new_id + '"');

		jQuery('#pmpro_courses_add_section').parent('p').before(new_section_html);

		// Grab the just-inserted section and update hidden field and some other values we need with the new ID.
		var inserted_section = jQuery('.pmpro_courses_lessons-section[data-section-id="' + new_id + '"]');
		inserted_section.find('input[name="pmpro_course_lessons_section_id[]"]').val(new_id);
		inserted_section.find('label[for^="pmpro_course_lessons_section_name_"]').attr('for', 'pmpro_course_lessons_section_name_' + new_id);
		inserted_section.find('input[id^="pmpro_course_lessons_section_name_"]').attr('id', 'pmpro_course_lessons_section_name_' + new_id);

		// Make the table sortable and select2.
		pmpro_courses_select2();
		pmpro_courses_make_table_sortable( inserted_section.find('.pmpro_courses_lesson_table') );

		pmpro_courses_prep_click_events();
		jQuery('#pmpro_courses_add_section').parent('p').prev().find('input').focus().select();
	});

	// Delete a specific section.
	jQuery('.pmpro_courses_lessons-section-buttons-button.delete-section-btn').unbind('click').on('click', function () {
		var the_section = jQuery(this).closest('.pmpro_courses_lessons-section');
		var section_name = the_section.find('input[name="pmpro_course_lessons_section_name[]"]').val();

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
