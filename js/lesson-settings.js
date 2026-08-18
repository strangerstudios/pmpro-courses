/**
 * Lesson Settings meta box.
 *
 * Shows the release date only when the drip method is a specific date, and hides drip settings
 * entirely for free lessons. Rows are hidden with CSS rather than disabled so their values still
 * submit, which keeps a chosen date intact while the drip method is set to none.
 */
jQuery( function( $ ) {
	var $drip_method = $( '#pmpro_courses_drip_method' );

	if ( ! $drip_method.length ) {
		return;
	}

	var $free           = $( '#pmpro_courses_bypass_restriction' );
	var $drip_rows      = $( '.pmpro_courses_lesson_drip' );
	var $drip_date_row  = $( '.pmpro_courses_lesson_drip_date' );

	function pmpro_courses_toggle_lesson_drip() {
		if ( $free.is( ':checked' ) ) {
			$drip_rows.hide();
			return;
		}

		$drip_rows.show();
		$drip_date_row.toggle( 'date' === $drip_method.val() );
	}

	$free.on( 'change', pmpro_courses_toggle_lesson_drip );
	$drip_method.on( 'change', pmpro_courses_toggle_lesson_drip );

	pmpro_courses_toggle_lesson_drip();
} );
