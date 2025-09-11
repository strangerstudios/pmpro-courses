<?php

/**
 * Run an update function.
 */
function pmpro_courses_upgrade_1_2() {
	pmpro_courses_create_progress_table();
	update_option( 'pmpro_courses_db_version', '1.3' );
	return 1.3;
}

/**
 * Create the table for progress tracking.
 *
 * @since TBD
 * @return void
 */
function pmpro_courses_create_progress_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'pmpro_courses_user_lesson_progress';
	$sql = "CREATE TABLE {$table_name} (
	user_id bigint(20) unsigned NOT NULL,
	lesson_id bigint(20) unsigned NOT NULL,
	completed_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (user_id, lesson_id),
	KEY idx_lesson_id (lesson_id),
	KEY idx_completed_at (completed_at))";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}