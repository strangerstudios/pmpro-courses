jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses_lesson_toggle", function(){

		var checkbox = jQuery(this);
		var lid = checkbox.attr('data-lid');
		var complete = checkbox.is(":checked") ? 1 : 0;

		var data = {
			action: 'pmpro_courses_toggle_lesson_progress',
			lid: lid,
			complete: complete
		}

		jQuery.get( pmpro_courses.ajaxurl, data, function( response ){
			if ( response ) {
				checkbox.parent().replaceWith( response );
			}
		});
	});

});