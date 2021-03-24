<?php 
function pmproc_record_progress_ajax(){

	if( !empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'pmproc_record_progress' ){

		$user = wp_get_current_user();

		$course_id = intval( $_REQUEST['cid'] );

		if( $user ){

			$user_id = $user->ID;

			$get_progress = get_user_meta( $user_id, 'pmproc_progress_'.$course_id, true );

			if( empty( $get_progress ) ){
				$get_progress = array( intval( $_REQUEST['lid'] ) );
				$updated = update_user_meta( $user_id, 'pmproc_progress_'.$course_id, $get_progress );
				unset( $get_progress );
			} else {
				$get_progress[] = intval( $_REQUEST['lid'] );
				$updated = update_user_meta( $user_id, 'pmproc_progress_'.$course_id, $get_progress );
				unset( $get_progress );
			}

			$next_lesson = pmproc_get_next_lesson( $_REQUEST['lid'], $course_id );

			echo json_encode( array( 'status' => $updated, 'next_lesson' => $next_lesson ) );

			wp_die();

		}		

	}

}
add_action( 'wp_ajax_pmproc_record_progress', 'pmproc_record_progress_ajax' );