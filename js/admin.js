
function pmproc_editPost(post_id){
	jQuery('#pmproc_post').val(post_id).trigger("change");
	jQuery('#pmproc_save').html('Save');
	location.href = "#pmproc_edit_post";
}

function pmproc_removePost(post_id) {
	
	var data = {
		action: 'pmproc_remove_course',
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
			jQuery('#pmproc_save').removeAttr('disabled');												
		},
		success: function(responseHTML){
			if (responseHTML == 'error'){
				alert('Error removing lesson [2]');
				//enable save button
				jQuery('#pmproc_save').removeAttr('disabled');	
			}else{
				jQuery('#pmproc_table tbody').html(responseHTML);
				// pmproc_Setup();
			}																						
		}
	});
}

function pmproc_updatePost() {

	jQuery(this).attr('disabled', 'true');	

	var lesson_id = jQuery("#pmproc_post").val();

	var data = {
		action: 'pmproc_update_course',
		course: pmpro_courses.course_id,
		lesson: lesson_id
	}
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'html',
		data: data,
		error: function(xml){
			alert('Error saving lesson to course [1]');
			//enable save button
			jQuery('#pmproc_save').html('Save');	
			jQuery('#pmproc_save').removeAttr('disabled');												
		},
		success: function(responseHTML){
			if (responseHTML == 'error'){
				alert('Error saving lesson to course [2]');
				//enable save button
				jQuery('#pmproc_save').html('Save');	
				jQuery('#pmproc_save').removeAttr('disabled');		
			}else{
				jQuery('#pmproc_table tbody').html(responseHTML);
				// pmproc_Setup();
			}																						
		}
	});
}

function pmproc_Setup() {
	jQuery('#pmproc_post').select2({width: 'elements'});
	
	jQuery('#pmproc_save').click(function() {
		if( jQuery(this).attr('disabled') !== 'true' ){
			pmproc_updatePost();
		}
	});
}

jQuery(document).ready(function(jQuery) {
	pmproc_Setup();
});
