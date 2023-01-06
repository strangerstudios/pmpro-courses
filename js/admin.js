
function pmpro_courses_edit_post(post_id, order){
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
		lesson: post_id
	}

	jQuery.ajax({
		url: ajaxurl,
		type:'POST',
		timeout:2000,
		dataType: 'html',
		data: data,
		error: function(xml){
			alert('Error removing lesson [1]');
			//enable save button
			jQuery('#pmpro_courses_save').removeAttr('disabled');												
		},
		success: function(responseHTML){
			if (responseHTML == 'error'){
				alert('Error removing lesson [2]');
				//enable save button
				jQuery('#pmpro_courses_save').removeAttr('disabled');	
			}else{
				jQuery('#pmpro_courses_table tbody').html(responseHTML);
			}																						
		}
	});
}

function pmpro_courses_update_post() {

	jQuery(this).attr('disabled', 'true');	

	var lesson_id = jQuery('#pmpro_courses_post').val();
	var order = jQuery('#pmpro_courses_order').val();

	var data = {
		action: 'pmpro_courses_update_course',
		course: pmpro_courses.course_id,
		lesson: lesson_id,
		order: order
	}
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'html',
		data: data,
		error: function(xml){
			alert('Error saving lesson to course [1]');
			//enable save button
			jQuery('#pmpro_courses_save').html('Save');	
			jQuery('#pmpro_courses_save').removeAttr('disabled');												
		},
		success: function(responseHTML){
			if (responseHTML == 'error'){
				alert('Error saving lesson to course [2]');
				//enable save button
				jQuery('#pmpro_courses_save').html('Save');
				jQuery('#pmpro_courses_save').removeAttr('disabled');		
			}else{
				jQuery('#pmpro_courses_table tbody').html(responseHTML);
				jQuery('#pmpro_courses_post').val(null).trigger('change');
				jQuery('#pmpro_courses_order').val('');
				jQuery('#pmpro_courses_save').html('Add to Course');
			}																						
		}
	});
}

function pmpro_courses_setup() {
	jQuery('#pmpro_courses_post').select2({width: 'elements'});
	
	jQuery('#pmpro_courses_order').keypress(function (e) {
		if (e.which == 13) {
			pmpro_courses_update_post();
			return false;
		}
	});
	
	jQuery('#pmpro_courses_save').click(function() {
		if( jQuery(this).attr('disabled') !== 'true' ){
			//Show that the courses is being 
			jQuery( this ).html( pmpro_courses.adding );
			pmpro_courses_update_post();
		}
	});
}

jQuery(document).ready(function(jQuery) {
	pmpro_courses_setup();
});
