jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses-mark-complete-action", function(){

		var lid = jQuery(this).attr('lid');
		var cid = jQuery(this).attr('cid');

		var data = {
			action: 'pmproc_record_progress',
			lid: lid,
			cid: cid
		}

		jQuery.post( pmproc_ajaxurl, data, function( response ){
			if( response ){
				response = JSON.parse( response );
				if( response.next_lesson ){
					window.location.href = response.next_lesson;
				}
			}

		});
	});

});