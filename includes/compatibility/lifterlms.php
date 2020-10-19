<?php

function pmproc_lifterlms_course_page_meta_wrapper() {
	add_meta_box( 'pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', 'course', 'side', 'high' );
}

function pmproc_lifterlms_init() {
	/*
		Add meta boxes to edit events page
	*/
	if ( is_admin() && defined( 'PMPRO_VERSION' ) ) {
		add_action( 'admin_menu', 'pmproc_lifterlms_course_page_meta_wrapper' );
	}
}
add_action( 'init', 'pmproc_lifterlms_init', 20 );

function pmproc_lifterlms_course_update_user_enrolment($user_id) {
	global $wpdb;
	// get all published courses
	$courses = get_posts([
		'post_type'     => 'course',
		'post_status'   => 'publish',
		'numberposts' 	=> -1
	]);

	foreach( $courses as $course ) {
		$course_levels = $wpdb->get_col(
			"SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $course->ID ) . "'"
		);
		if ( pmpro_hasMembershipLevel( $course_levels, $user_id ) ) {
			// enroll them to the course
			llms_enroll_student( $user_id, $course->ID );
		} else {
			// unenroll them if they're not enrolled
			if ( llms_is_user_enrolled( $user_id, $course->ID ) ) {
				llms_unenroll_student( $user_id, $course->ID );
			}
		}
	}
}

function pmproc_lifterlms_remove_course_access_on_order_deletion( $order_id, $order ) {
	$user = $order->getUser();
	pmproc_lifterlms_course_update_user_enrolment( $user->ID );
}
add_action( 'pmpro_delete_order', 'pmproc_lifterlms_remove_course_access_on_order_deletion', 10, 2 );

function pmproc_lifterlms_after_change_membership_level( $level, $user_id, $cancel_level ) {
	pmproc_lifterlms_course_update_user_enrolment( $user_id );
}
add_action( 'pmpro_after_change_membership_level', 'pmproc_lifterlms_after_change_membership_level', 10, 3 );

function pmproc_lifterlms_update_course_access_by_order( $order ) {
	$user = $order->getUser();
	pmproc_lifterlms_course_update_user_enrolment( $user->ID );
}
add_action( 'pmpro_subscription_cancelled', 'pmproc_lifterlms_update_course_access_by_order', 10, 1 );
add_action( 'pmpro_subscription_expired', 'pmproc_lifterlms_update_course_access_by_order', 10, 1 );
add_action( 'pmpro_subscription_recuring_restarted', 'pmproc_lifterlms_update_course_access_by_order', 10, 1 );
add_action( 'pmpro_subscription_recuring_stopped', 'pmproc_lifterlms_update_course_access_by_order', 10, 1 );
add_action( 'pmpro_updated_order', 'pmproc_lifterlms_update_course_access_by_order', 10, 1 );

function pmproc_lifterlms_pre_post_update($post_id, $data) {
	global $wpdb;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) return;
	if ( $data['post_type'] !== 'course' ) {
		return;
	}

	$cache = get_transient(
		sprintf( 'pmproc_lifterlms_course_%s_levels', $post_id )
	);
	if ( ! empty( $cache ) ) {
		return;
	}

	$course_levels = $wpdb->get_col(
		"SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $post_id ) . "'"
	);
	sort( $course_levels );
	// to know if the course's required levels have been changed,
	// save the course's current membership levels
	set_transient(
		sprintf( 'pmproc_lifterlms_course_%s_levels', $post_id ),
		implode( ',', $course_levels ),
		60*60
	);
}
add_action( 'pre_post_update', 'pmproc_lifterlms_pre_post_update', 10, 2);

function pmproc_lifterlms_save_post_course($post_id, $post, $update) {
	global $wpdb;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
	if ( $post->post_type !== 'course' ) {
		return;
	}

	$cache = get_transient(
		sprintf( 'pmproc_lifterlms_course_%s_levels', $post_id )
	);
	$course_previous_levels = explode( ',', $cache );
	delete_transient( sprintf( 'pmproc_lifterlms_course_%s_levels', $post_id ) );
	if ( empty( $cache ) && $update ) {
		return;
	}

	$course_levels = $wpdb->get_col(
		"SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $post_id ) . "'"
	);
	sort( $course_levels );

	// membership requirement for the course is still the same
	if ( $course_levels === $course_previous_levels ) {
		return;
	}

	$enrolled_student_ids = array();
	$done_fetching_students = false;
	$page = 0;
	$limit = 50;

	while( ! $done_fetching_students ) {
		$enrolled_students_batch = llms_get_enrolled_students( $post_id, 'enrolled', $limit, $page );
		$enrolled_student_ids = array_merge( $enrolled_student_ids, $enrolled_students_batch );
		if ( count( $enrolled_students_batch ) == $limit ) {
			$page++;
		} else{
			$done_fetching_students = true;
		}
	}

	$course_levels = $wpdb->get_col(
		"SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $post_id ) . "'"
	);
	$members_with_levels_sql = "SELECT user_id FROM {$wpdb->pmpro_memberships_users}
					WHERE `status` = 'active'
					AND `membership_id` IN ('" . implode( "', '", $course_levels ) . "')";
	$members_with_levels = $wpdb->get_col( $members_with_levels_sql );

	// members who SHOULD be enrolled but aren't at the moment
	$unenrolled_qualifiers = array_diff( $members_with_levels, $enrolled_student_ids );
	foreach( $unenrolled_qualifiers as $unenrolled_qualifier ) {
		llms_enroll_student( $unenrolled_qualifier, $post_id );
	}

	// members who WERE enrolled but are no longer legible
	$ineligible_members = array_diff( $enrolled_student_ids, $members_with_levels );
	foreach ( $ineligible_members as $ineligible_member ) {
		llms_unenroll_student( $ineligible_member, $post_id );
	}
}
add_action( 'save_post', 'pmproc_lifterlms_save_post_course', 11, 3 );
