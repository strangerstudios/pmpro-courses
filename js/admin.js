
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

function pmpro_courses_update_post() {

	jQuery(this).attr('disabled', 'true');

	var lesson_id = jQuery('#pmpro_courses_post').val();
	//Insert the lesson into the course at the end for now. Get the order from table TD
	const order = parseInt( jQuery( '#pmpro_courses_table tr:last ' ).find( 'td' ).eq( 0 ).text() )+ 1;

	var data = {
		action: 'pmpro_courses_update_course',
		course: pmpro_courses.course_id,
		lesson: lesson_id,
		order: order,
		nonce: pmpro_courses.nonce
	}
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'html',
		data: data,
		error: function (xml) {
			alert('Error saving lesson to course [1]');
			//enable save button
			jQuery('#pmpro_courses_save').html('Save');
			jQuery('#pmpro_courses_save').removeAttr('disabled');
		},
		success: function (responseHTML) {
			if (responseHTML == 'error') {
				alert('Error saving lesson to course [2]');
				//enable save button
				jQuery('#pmpro_courses_save').html('Save');
				jQuery('#pmpro_courses_save').removeAttr('disabled');
			} else {
				jQuery('#pmpro_courses_table tbody').html(responseHTML);
				jQuery('#pmpro_courses_post').val(null).trigger('change');
				jQuery('#pmpro_courses_order').val('');
				jQuery('#pmpro_courses_save').html('Add to Course');
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
		jQuery('#pmpro_courses_post').select2({ width: 'elements' });

		jQuery('#pmpro_courses_order').keypress(function (e) {
			if (e.which == 13) {
				pmpro_courses_update_post();
				return false;
			}
		});

		jQuery('#pmpro_courses_save').click(function () {
			if (jQuery(this).attr('disabled') !== 'true') {
				//Show that the courses is being 
				jQuery(this).html(pmpro_courses.adding);
				pmpro_courses_update_post();
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

/**
 * Show a notice on the page using the WordPress notice styles.
 *
 * @param {string} message - The message to display in the notice
 * @param {string} type - The type of notice to display (error, warning, success, info)
 * @return {void}
 * @since TBD
 */
function pmpro_courses_show_notice( message, type )  {
	jQuery('.components-notice-list.components-editor-notices__dismissible').empty()
		.append( jQuery( '<div/>' ).addClass( 'notice is-dismissible notice-' + type)
			.append( jQuery( '<p/>' ).append( message) , jQuery( '<button/>' ).addClass( 'notice-dismiss' )
				.append( jQuery( '<span />' ).addClass( 'screen-reader-text' ).text( 'Dismiss this notice' ) ) ) );

	// Dismiss the notice when the dismiss button is clicked
	jQuery( '.notice-dismiss' ).click( function () {
		jQuery( this ).parent().remove();
	});
}

/**
 * Update the order of the lessons in the course UI table 
 *
 * @return {void}
 * @since TBD
 */
function pmpro_courses_update_course_order_ui() {
	$tbody = jQuery( '#pmpro_courses_table tbody' );
	//Iterate over the tr elements in the tbody and update the order in the TD
	$tbody.find('tr').each( function ( index ) {
		jQuery(this).find( 'td' ).eq( 0 ).text( index + 1 );
	});

}
/**
 * Update the order of the lessons in the course
 * 
 * @param {jQuery} $tbody - The tbody element containing the lessons
 * @return {void}
 * @since TBD
 */
function pmpro_courses_update_lessons_order( $tbody ) {
	const  $lesson_ids = $tbody.find( 'tr' ).map( function () {
		return jQuery(this).data('lesson_id');
	}).get();

	// Request body
	const data = {
		action: 'pmpro_courses_update_course_order',
		course: pmpro_courses.course_id,
		lessons: $lesson_ids,
		nonce: pmpro_courses.nonce
	}

	// Send the request
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'JSON',
		data: data,
		//Error callback
		error: function ( xml ) {
			pmpro_courses_show_notice('Error updating lesson order', 'error');
		},
		//Success callback
		success: function ( response ) {
			if ( ! response || ! response.success ) {
				message = response.message ? response.message : 'Error updating lesson order';
				pmpro_courses_show_notice( message, 'error' );
			} else {
				pmpro_courses_show_notice( response.message, 'success' );
				// Update the UI
				pmpro_courses_update_course_order_ui();
			}
		}
	});
}

jQuery(document).ready(function ( $ ) {
	pmpro_courses_setup();
	$( '#pmpro_courses_table tbody' ).sortable({
		stop: function ( event, ui ) {
			pmpro_courses_update_lessons_order( $( this ) );
		}
	});
});
