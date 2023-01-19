<?php
/**
 * Plugin Name: Paid Memberships Pro - Courses for Membership Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/
 * Description: Create courses and lessons for members. Integrates LMS plugins with Paid Memberships Pro.
 * Version: 1.2.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-courses
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

define( 'PMPRO_COURSES_VERSION', dirname( __FILE__ ) );
define( 'PMPRO_COURSES_DIR', dirname( __FILE__ ) );
define( 'PMPRO_COURSES_BASENAME', plugin_basename( __FILE__ ) );

// Includes.
require_once PMPRO_COURSES_DIR . '/includes/common.php';
require_once PMPRO_COURSES_DIR . '/includes/admin.php';
require_once PMPRO_COURSES_DIR . '/includes/settings.php';
require_once PMPRO_COURSES_DIR . '/includes/blocks.php';

// Modules.
function pmpro_courses_setup_modules() {
	require_once PMPRO_COURSES_DIR . '/includes/modules/default.php';
	$default_module = new PMPro_Courses_Module();
	require_once PMPRO_COURSES_DIR . '/includes/modules/learndash.php';
	$learndash_module = new PMPro_Courses_LearnDash();
	require_once PMPRO_COURSES_DIR . '/includes/modules/lifterlms.php';
	$lifterlms_module = new PMPro_Courses_LifterLMS();
	require_once PMPRO_COURSES_DIR . '/includes/modules/senseilms.php';
	$senseilms_module = new PMPro_Courses_SenseiLMS();
	require_once PMPRO_COURSES_DIR . '/includes/modules/tutorlms.php';
	$tutorlms_module = new PMPro_Courses_TutorLMS();
}
add_action( 'plugins_loaded', 'pmpro_courses_setup_modules' );

/**
 * Default settings on first load and updates.
 */
function pmpro_courses_admin_init() {
	$db_version = get_option( 'pmpro_courses_db_version', '' );
	if ( $db_version < 1 ) {
		// Figure out which modules to enable.
		$modules = array();

		// Turn on LearnDash if it's already active.
		if( defined( 'LEARNDASH_VERSION' ) ) {
			$modules[] = 'learndash';
		}

		// If no other modules are active, install our core Courses and Lessons.
		if ( empty( $modules ) ) {
			$modules[] = 'default';
		}
		update_option( 'pmpro_courses_modules', $modules );

		// Save DB version.
		update_option( 'pmpro_courses_db_version', 1 );
	}
}
add_action( 'admin_init', 'pmpro_courses_admin_init' );

/**
 * Maybe flush rewrite rules.
 * Fires on admin_init 5 to run after CPTs are set up,
 * but before the settings are saved.
 * @since 1.0
 */
function pmpro_courses_flush_rewrite_rules() {
	$flush = get_transient( 'pmpro_courses_flush_rewrite_rules' );
	if ( ! empty( $flush ) ) {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$wp_rewrite->init();
		delete_transient( 'pmpro_courses_flush_rewrite_rules' );
	}
}
add_action( 'admin_init', 'pmpro_courses_flush_rewrite_rules', 5 );

/**
 * Tie into GlotPress
 *
 * @return void
 */
function pmpro_courses_load_textdomain() {
	load_plugin_textdomain( 'pmpro-courses', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpro_courses_load_textdomain' );

/**
 * Enqueue Admin Scripts and Styles
 */
function pmpro_courses_admin_styles( $hook ) {

	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && 'pmpro_course' == get_post_type() ) {

		wp_enqueue_style( 'pmpro-courses-admin', plugins_url( 'css/admin.css', __FILE__ ), '', PMPRO_COURSES_VERSION, 'screen' );
		wp_enqueue_style( 'pmpro-courses-select2', plugins_url( 'css/select2.css', __FILE__ ), '', PMPRO_COURSES_VERSION, 'screen' );
		wp_enqueue_script( 'pmpro-courses-select2', plugins_url( 'js/select2.js', __FILE__ ), array( 'jquery' ), PMPRO_COURSES_VERSION );
		wp_register_script( 'pmpro_courses', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), null, true );

		if ( ! empty( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = '';
		}

		$localize = array(
			'course_id'      => $post_id,
			'save'           => esc_html__( 'Save', 'pmpro-courses' ),
			'saving'         => esc_html__( 'Saving...', 'pmpro-courses' ),
			'adding'         => esc_html__( 'Adding...', 'pmpro-courses' ),
			'saving_error_1' => esc_html__( 'Error saving lesson [1]', 'pmpro-courses' ),
			'saving_error_2' => esc_html__( 'Error saving lesson [2]', 'pmpro-courses' ),
			'remove_error_1' => esc_html__( 'Error removing lesson [1]', 'pmpro-courses' ),
			'remove_error_2' => esc_html__( 'Error removing lesson [2]', 'pmpro-courses' ),
		);

		wp_localize_script( 'pmpro_courses', 'pmpro_courses', $localize );
		wp_enqueue_script( 'pmpro_courses' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmpro_courses_admin_styles' );

/**
 * Enqueue Frontend Scripts and Styles
 */
function pmpro_courses_frontend_styles(){

	global $post;

	if(
		is_singular( array( 'pmpro_course', 'pmpro_lesson' ) ) ||
		( $post && has_shortcode( $post->post_content, 'pmpro_all_courses' ) ) ||
		( $post && has_shortcode( $post->post_content, 'pmpro_my_courses' ) )
	){
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'pmpro-courses-styles', plugins_url( 'css/frontend.css', __FILE__ ) );
		wp_enqueue_script( 'pmpro-courses-scripts', plugins_url( 'js/frontend.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'pmpro-courses-scripts', 'pmpro_courses', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'pmpro-courses-loading-bar-js', plugins_url( 'js/loading-bar.js', __FILE__ ) );
		wp_enqueue_style( 'pmpro-courses-loading-bar-css', plugins_url( 'css/loading-bar.css', __FILE__ ) );
	}

}
add_action( 'wp_enqueue_scripts', 'pmpro_courses_frontend_styles' );