<?php
/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 * @since 1.2.2 Changed name. Flushing rewrite rules when other plugins are activated too.
 */
function pmpro_courses_activation() {
	// If this plugin is being acitvated, show a notice.
	if ( current_filter() === 'activate_' . PMPRO_COURSES_BASENAME ) {
		set_transient( 'pmpro-courses-admin-notice', true, 5 );
	}
	
	// Trigger a rewrite rule flush in case the default module is on.
	set_transient( 'pmpro_courses_flush_rewrite_rules', 1 );
}
register_activation_hook( PMPRO_COURSES_BASENAME, 'pmpro_courses_activation' );
register_activation_hook( 'lifterlms/lifterlms.php', 'pmpro_courses_activation' );
register_activation_hook( 'tutor/tutor.php', 'pmpro_courses_activation' );
register_activation_hook( 'sensei-lms/lsensei-lms.php', 'pmpro_courses_activation' );
register_activation_hook( 'sfwd-lms/sfwd_lms.php', 'pmpro_courses_activation' );

/**
 * Runs only when the plugin is deactivated.
 *
 * @since 1.2.2
 */
function pmpro_courses_deactivation() {
	// Trigger a rewrite rule flush in case the default module is on.
	set_transient( 'pmpro_courses_flush_rewrite_rules', 1 );
}
register_deactivation_hook( PMPRO_COURSES_BASENAME, 'pmpro_courses_deactivation' );
register_deactivation_hook( 'lifterlms/lifterlms.php', 'pmpro_courses_deactivation' );
register_deactivation_hook( 'tutor/tutor.php', 'pmpro_courses_deactivation' );
register_deactivation_hook( 'sensei-lms/lsensei-lms.php', 'pmpro_courses_deactivation' );
register_deactivation_hook( 'sfwd-lms/sfwd_lms.php', 'pmpro_courses_deactivation' );

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmpro_courses_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmpro-courses-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p>
			<?php 
				esc_html_e( 'Thank you for activating.', 'pmpro-courses' );
				echo ' <a href="' . esc_url( get_admin_url( null, 'edit.php?post_type=pmpro_course' ) ) . '">';
				esc_html_e( 'Click here to add your first course.', 'pmpro-courses' );
				echo '</a>';
			?>
			</p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmpro-courses-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmpro_courses_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmpro_courses_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . esc_url( get_admin_url( null, 'admin.php?page=pmpro-courses-settings' ) ) . '">' . esc_html__( 'Settings', 'pmpro-courses' ) . '</a>',
		);

		$links = array_merge( $new_links, $links );
	}
	return $links;
}
add_filter( 'plugin_action_links_' . PMPRO_COURSES_BASENAME, 'pmpro_courses_plugin_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmpro_courses_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-courses.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/' ) . '" title="' . esc_attr__( 'View Documentation', 'pmpro' ) . '">' . esc_html__( 'Docs', 'pmpro-courses' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr__( 'Visit Customer Support Forum', 'pmpro' ) . '">' . esc_html__( 'Support', 'pmpro-courses' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_courses_plugin_row_meta', 10, 2 );
