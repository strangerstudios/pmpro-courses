jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses_lesson_toggle", function(){

		var checkbox = jQuery(this);
		var lid = checkbox.attr('data-lid');
		var cid = checkbox.attr('data-cid');

		var data = {
			action: 'pmpro_courses_toggle_lesson_progress',
			lid: lid,
			cid: cid
		}

		jQuery.get( pmpro_courses.ajaxurl, data, function( response ){
			if( response ) {				
				checkbox.parent().replaceWith( response );
			}
		});
	});

});