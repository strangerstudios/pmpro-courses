jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses-button", function(){

		var button = jQuery(this);
		var lid = button.attr('data-lid');
		var cid = button.attr('data-cid');

		var data = {
			action: 'pmpro_courses_toggle_lesson_progress',
			lid: lid,
			cid: cid
		}

		jQuery.get( pmpro_courses.ajaxurl, data, function( response ){
			if( response ) {				
				button.replaceWith( response );
			}
		});
	});

});