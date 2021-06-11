<?php
/**
 * Admin settings page for Courses for Membership Add On.
 * 
 */

/**
 * Add a Course page for settings under the Memberships menu.
 */
function pmpro_courses_settings_page() {	
	// Course settings page under Memberships menu.
	add_submenu_page( 'pmpro-dashboard', __('Paid Memberships Pro Courses - Settings', 'pmpro-courses'), __('Courses', 'pmpro-courses'), 'manage_options', 'pmpro-courses-settings', 'pmpro_courses_settings' );
	
	if ( pmpro_courses_is_module_active( 'default' ) ) {
		// Add New Lesson menu page under Courses menu.
		add_submenu_page( 'edit.php?post_type=pmpro_course', __('Paid Memberships Pro Courses - Add New Lesson', 'pmpro-courses'), __('Add New Lesson', 'pmpro-courses'), 'manage_options', 'post-new.php?post_type=pmpro_lesson', '', 5 );
		
		// Mirror the Settings menu item under Courses to go to same page under Memberships menu if PMPro is active.
		if ( defined( 'PMPRO_DIR' ) ) {
			add_submenu_page( 'edit.php?post_type=pmpro_course', __('Paid Memberships Pro Courses - Settings', 'pmpro-courses'), __('Settings', 'pmpro-courses'), 'manage_options', 'admin.php?page=pmpro-courses-settings', '', 10 );
		}
	}	
}
add_action( 'admin_menu', 'pmpro_courses_settings_page' );

function pmpro_courses_settings() {
	require_once PMPRO_COURSES_DIR . '/includes/adminpages/settings.php';
}

function pmpro_courses_settings_save() {
	if ( isset( $_REQUEST['pmpro_courses_save_settings'] ) ) {
		if ( ! empty( $_REQUEST['pmpro_courses_modules'] ) ) {
			// Make sure they are valid modules.
			$all_modules = pmpro_courses_get_modules();
			$active_modules = array();
			foreach( $_REQUEST['pmpro_courses_modules'] as $active_module ) {
				if ( in_array( $active_module, array_keys( $all_modules ) ) ) {
					$active_modules[] = $active_module;
				}
			}
			
			// Save the option.
			update_option( 'pmpro_courses_modules', $active_modules );			
		} else {
			update_option( 'pmpro_courses_modules', array() );
		}
	}
}
add_action( 'admin_init', 'pmpro_courses_settings_save' );

function pmpro_courses_save_notice() {
	if ( isset( $_REQUEST['pmpro_courses_save_settings'] ) ) {
		echo sprintf( "<div class='updated'><p>%s</p></div>", __( 'Settings saved successfully.', 'pmpro-courses') );
	}
}
add_action( 'admin_notices', 'pmpro_courses_save_notice', 10 );
