<?php
/**
 * Plugin Name: Paid Memberships Pro - Courses
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/courses/
 * Description: Courses, Lessons, and eLearning for Paid Memberships Pro.
 * Version: .1
 * Author: pbrocks, strangerstudios
 * Author URI: https://www.strangerstudios.com
 * Text Domain: pmpro-courses
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

define( 'PMPRO_COURSES_VERSION', dirname( __FILE__ ) );
define( 'PMPRO_COURSES_DIR', dirname( __FILE__ ) );
define( 'PMPRO_COURSES_BASENAME', plugin_basename( __FILE__ ) );

// Includes.
require_once PMPRO_COURSES_DIR . '/includes/common.php';
require_once PMPRO_COURSES_DIR . '/includes/post-types/courses.php';
require_once PMPRO_COURSES_DIR . '/includes/post-types/lessons.php';
require_once PMPRO_COURSES_DIR . '/includes/admin.php';

//Integrations
require_once PMPRO_COURSES_DIR . '/includes/compatibility/learndash.php';

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
 * Enqueue CSS
 */
function pmpro_courses_admin_styles() {
	wp_register_style( 'pmpro-courses-admin', plugins_url( 'inc/css/pmpro-courses-admin.css', __FILE__ ), array(), time() );
	wp_enqueue_style( 'pmpro-courses-admin' );
}
add_action( 'admin_enqueue_scripts', 'pmpro_courses_admin_styles' );