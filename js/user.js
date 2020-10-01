jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmproc_button_mark_complete_action", function(){

		var lid = jQuery(this).attr('lid');
		var cid = jQuery(this).attr('cid');

		var data = {
			action: 'pmproc_record_progress',
			lid: lid,
			cid: cid
		}

		jQuery.post( ajaxurl, data, function( response ){

			if( response ){
				//Needs work - better handling of the response
			}

		});
	});

});