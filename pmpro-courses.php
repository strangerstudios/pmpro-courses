<?php
/**
 * Plugin Name: Paid Memberships Pro - Courses for Membership Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/
 * Description: Create courses and lessons for members. Integrates LMS plugins with Paid Memberships Pro.
 * Version: 0.1
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
require_once PMPRO_COURSES_DIR . '/includes/post-types/courses.php';
require_once PMPRO_COURSES_DIR . '/includes/post-types/lessons.php';
require_once PMPRO_COURSES_DIR . '/includes/admin.php';
require_once PMPRO_COURSES_DIR . '/includes/widgets.php';

/**
 * If an integration is active, include its file
 */
$integrations = pmpro_getOption( 'pmproc_integrations' );
$integ_array = explode( ",", $integrations );

$integration_files = apply_filters( 'pmproc_includes_integrations', array(
	'learndash' => PMPRO_COURSES_DIR . '/includes/compatibility/learndash.php'
) );

if( !empty( $integ_array ) ){
	foreach( $integ_array as $integration ){
		if( isset( $integration_files[$integration] ) ){
			require_once $integration_files[$integration];
		}
	}
}

function pmproc_ld_settings( $integrations ){

	$integrations[] = array(
		'name' => __('LearnDash', 'pmpro-courses'),
		'slug' => 'learndash'
	);

	return $integrations;

}
add_filter( 'pmproc_settings_integrations', 'pmproc_ld_settings', 10, 1 );

/**
 * Content filter to show additional course information on the single course page.
 */
function pmpro_courses_the_content_course( $content ) {
	global $post;
	if ( is_singular( 'pmpro_course' ) ) {
		// This is a single pmpro_course CPT, show additional content before and after post_content.
		$before_the_content = '';
		$course_categories_list = get_the_term_list( $post->ID, 'pmpro_course_category', '', __( ', ', 'pmpro-courses' ) );
		if ( $course_categories_list ) {
			$before_the_content .= sprintf(
				/* translators: %s: list of categories. */
				'<p><span class="cat-links">' . esc_html__( 'Course Category: %s', 'pmpro-courses' ) . ' </span></p>',
				$course_categories_list
			);
		}

		$show_progress_bar = apply_filters( 'pmproc_show_progress_bar', true );
		if ( $show_progress_bar ) {
			$before_the_content .= pmproc_display_progress_bar( $post->ID );
		}

		// Show a list of lessons from a custom template or the default lesson list after the_content.
		$path = dirname(__FILE__);
		$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
		$custom_file = $custom_dir."lessons.php";

		// Load custom or default templates.
		if( file_exists($custom_file ) ){
			$include_file = $custom_file;
		} else {
			$include_file = $path . "/templates/lessons.php";
		}

		ob_start();
		include $include_file;
		$after_the_content = ob_get_contents();
		ob_end_clean();

		// Return the content after appending new pre and post sections.
		return $before_the_content . $content . $after_the_content;
	}
	return $content;
}
add_filter( 'the_content', 'pmpro_courses_the_content_course', 10, 1 );

/**
 * Content filter to show lesson and course information on the single lesson page.
 */
function pmpro_courses_the_content_lesson( $content ) {
	global $post;
	if ( is_singular( 'pmpro_lesson' ) ) {
		$course_id = get_post_meta( $post->ID, 'pmproc_parent', true );

		// This is a single pmpro_lesson CPT, show additional content before the_content.
		$before_the_content = '';
		if ( ! empty( $course_id ) ) {
			$before_the_content .= sprintf(
				/* translators: %s: link to the course for this lesson. */
				'<p>' . esc_html__( 'Back to: %s', 'pmpro-courses' ) . ' </span></p>',
				'<a href="' . get_permalink( $course_id ) . '" title="' . get_the_title( $course_id ) . '">' . get_the_title( $course_id ) . '</a>'
			);
		}

		$after_the_content = '<hr class="styled-separator is-style-wide" aria-hidden="true" />';

		// Show a link to mark the lesson complete or incomplete.
		$show_complete_button = apply_filters( 'pmproc_show_complete_button', true );
		if ( $show_complete_button ) {
			$lesson_status = pmproc_get_user_lesson_status( $post->ID, $course_id );
			if ( ! empty( $lesson_status ) ) {
				$after_the_content .= '<div class="pmpro_lesson-status">';
				if ( $lesson_status === 'complete' ) {
					// Filter the text shown as the button title for a completed lesson.
					$after_the_content .= esc_html( apply_filters( 'pmproc_lesson_complete_text', __('Lesson Completed', 'pmpro-courses' ) ) );
				} else {
					// Filter the text shown as the button title to mark a lesson complete.
					$after_the_content .= esc_html( apply_filters( 'pmproc_lesson_to_complete_text', __('Mark Lesson as Complete', 'pmpro-courses') ) );
				}
				$after_the_content .= pmproc_complete_button( $post->ID, $course_id );
				$after_the_content .= '</div>';
				$after_the_content .= '<hr class="styled-separator is-style-wide" aria-hidden="true" />';
			}
		}

		// Show a link to the previous and next lesson in the course.
		$show_lesson_navigation = apply_filters( 'pmproc_show_lesson_navigation', true );
		if ( $show_lesson_navigation ) {
			$pmproc_lesson_navigation_prev = pmproc_lesson_navigation( $post->ID, $course_id, 'prev' );
			$pmproc_lesson_navigation_next = pmproc_lesson_navigation( $post->ID, $course_id, 'next' );

			$after_the_content .= '<nav class="pmpro_lesson-navigation" role="navigation">';
			if ( ! empty( $pmproc_lesson_navigation_prev ) ) {
				$after_the_content .= '<div class="nav-previous"><span class="pmpro_lesson-navigation-label">' . __( 'Previous Lesson', 'pmpro-courses' ) . '</span>' . $pmproc_lesson_navigation_prev . '</div>';
			}
			if ( ! empty( $pmproc_lesson_navigation_next ) ) {
				$after_the_content .= '<div class="nav-next"><span class="pmpro_lesson-navigation-label">' . __( 'Next Lesson', 'pmpro-courses' ) . '</span>' . $pmproc_lesson_navigation_next . '</div>';
			}
			$after_the_content .= '</nav>';
		}

		return $before_the_content . $content . $after_the_content;
	}
	return $content;
}
add_filter( 'the_content', 'pmpro_courses_the_content_lesson', 10, 1 );

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
function pmpro_courses_admin_styles( $hook ) {
	
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && 'pmpro_course' == get_post_type() ) {

		wp_enqueue_style( 'pmpro-courses-admin', plugins_url( 'css/admin.css', __FILE__ ), '', PMPRO_COURSES_VERSION, 'screen' );
		wp_enqueue_style( 'pmproc-select2', plugins_url( 'css/select2.css', __FILE__ ), '', PMPRO_COURSES_VERSION, 'screen' );
		wp_enqueue_script( 'pmproc-select2', plugins_url( 'js/select2.js', __FILE__ ), array( 'jquery' ), PMPRO_COURSES_VERSION );
		wp_register_script( 'pmproc_pmpro', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), null, true );

		if ( ! empty( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = '';
		}

		$localize = array(
			'course_id'      => $post_id,
			'save'           => __( 'Save', 'pmpro-courses' ),
			'saving'         => __( 'Saving...', 'pmpro-courses' ),
			'saving_error_1' => __( 'Error saving lesson [1]', 'pmpro-courses' ),
			'saving_error_2' => __( 'Error saving lesson [2]', 'pmpro-courses' ),
			'remove_error_1' => __( 'Error removing lesson [1]', 'pmpro-courses' ),
			'remove_error_2' => __( 'Error removing lesson [2]', 'pmpro-courses' ),
		);

		wp_localize_script( 'pmproc_pmpro', 'pmpro_courses', $localize );
		wp_enqueue_script( 'pmproc_pmpro' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmpro_courses_admin_styles' );

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
		wp_enqueue_script( 'pmpro-courses-scripts', plugins_url( 'js/user.js', __FILE__ ) );
		wp_localize_script( 'pmpro-courses-scripts', 'pmproc_ajaxurl', admin_url( 'admin-ajax.php' ));
		wp_enqueue_script( 'pmpro-courses-loading-bar-js', plugins_url( 'js/loading-bar.js', __FILE__ ) );
		wp_enqueue_style( 'pmpro-courses-loading-bar-css', plugins_url( 'css/loading-bar.css', __FILE__ ) );
	}

}
add_action( 'wp_enqueue_scripts', 'pmpro_courses_frontend_styles' );

function pmpro_courses_update_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_update_course' ){
			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$order = intval( $_REQUEST['order'] );
			update_post_meta( $lesson, 'pmproc_parent', $course );
			wp_update_post( array( 'ID' => $lesson, 'menu_order' => $order ) );
			echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			wp_die();
		} 

	}

}
add_action( 'wp_ajax_pmproc_update_course', 'pmpro_courses_update_course_callback' );

function pmpro_courses_remove_course_callback(){

	if( !empty( $_REQUEST['action'] ) ){

		if( $_REQUEST['action'] == 'pmproc_remove_course' ){

			$course = intval( $_REQUEST['course'] );
			$lesson = intval( $_REQUEST['lesson'] );
			$deleted = delete_post_meta( $lesson, 'pmproc_parent' );

			if( $deleted ){
				echo pmpro_courses_build_lesson_html( pmpro_courses_get_lessons( $course ) );
			} else {
				echo 'error';
			}
			wp_die();
		}
	}

}
add_action( 'wp_ajax_pmproc_remove_course', 'pmpro_courses_remove_course_callback' );

/**
 * Adds "Course" column to the lessons page
 */
function pmpro_courses_lessons_columns( $columns ) {
    $columns['pmpro_course_assigned'] = __( 'Course', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_lesson_posts_columns', 'pmpro_courses_lessons_columns' );

function pmpro_courses_lessons_columns_content( $column, $post_id ) {
    switch ( $column ) {
        case 'pmpro_course_assigned' :
            echo pmpro_courses_get_course( $post_id ); 
            break;
    }
}
add_action( 'manage_pmpro_lesson_posts_custom_column' , 'pmpro_courses_lessons_columns_content', 10, 2 );

/**
 * Adds "Course" column to the lessons page
 */
function pmpro_courses_columns($columns) {
    $columns['pmpro_courses_num_lessons'] = __( 'Lesson Count', 'pmpro-courses' );
    $columns['pmpro_courses_level'] = __( 'Level', 'pmpro-courses' );
    return $columns;
}
add_filter( 'manage_pmpro_course_posts_columns', 'pmpro_courses_columns' );

function pmpro_courses_columns_content( $column, $course_id ) {
	global $wpdb;
	switch ( $column ) {
		case 'pmpro_courses_num_lessons' :
			$lesson_count = pmpro_courses_get_lesson_count( $course_id );
			printf( _n( '%s Lesson', '%s Lessons', $lesson_count, 'pmpro-courses' ), number_format_i18n( $lesson_count ) );
			break;
		case 'pmpro_courses_level' :
			$membership_levels = pmpro_getAllLevels( true, true );
			$course_levels = $wpdb->get_col( "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $course_id ) . "'" );
			$level_names = array();
			foreach ( $course_levels as $id ) {
				$level = pmpro_getLevel( $id );
				$level_names[] = $level->name;
			}
			if ( ! empty( $level_names ) ) {
				echo implode( ', ', $level_names );
			} else {
				echo '&#8212;';
			}
			break;
	}
}
add_action( 'manage_pmpro_course_posts_custom_column' , 'pmpro_courses_columns_content', 10, 2 );

function pmpro_courses_template_redirect() {

	global $post, $pmpro_pages;

	if( !empty( $post ) ){

		if( ( $post->post_type == 'pmpro_course' || $post->post_type == 'pmpro_lesson' ) && !is_archive() && !current_user_can( 'administrator' ) ){

			$post_id = $post->ID;

			//Choose a courses page to redirect to or go to the levels page? 	
			$redirect_to = apply_filters( 'pmpro_courses_redirect_to', pmpro_url( 'levels' ) );

			$access = pmpro_courses_check_level( $post_id );

			if( !$access && ( intval( $pmpro_pages['levels'] ) !== $post_id ) ){
				wp_redirect( $redirect_to );
				exit();
			}

		}

	}

}
add_action( 'template_redirect', 'pmpro_courses_template_redirect' );

function pmproc_has_course_access( $hasaccess, $mypost, $myuser, $post_membership_levels ) {

	if ( 'pmpro_courses' == $mypost->post_type ) {
		$hasaccess = pmpro_courses_check_level( $mypost->ID );
	}

	return $hasaccess;
}
add_filter( 'pmpro_has_membership_access_filter', 'pmproc_has_course_access', 99, 4 );

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

/**
 * Add a Course page for settings under the Memberships menu.
 *
 */
function pmpro_courses_settings_page() {
	// Add New Lesson menu page under Courses menu.
	add_submenu_page( 'edit.php?post_type=pmpro_course', __('Paid Memberships Pro Courses - Add New Lesson', 'pmpro-courses'), __('Add New Lesson', 'pmpro-courses'), 'manage_options', 'post-new.php?post_type=pmpro_lesson', '', 5 );

	// Course settings page under Memberships menu.
	add_submenu_page( 'pmpro-dashboard', __('Paid Memberships Pro Courses - Settings', 'pmpro-courses'), __('Courses', 'pmpro-courses'), 'manage_options', 'pmpro-courses-settings', 'pmpro_courses_settings' );
	// Mirror the Settings menu item under Courses to go to same page under Memberships menu.
	add_submenu_page( 'edit.php?post_type=pmpro_course', __('Paid Memberships Pro Courses - Settings', 'pmpro-courses'), __('Settings', 'pmpro-courses'), 'manage_options', 'admin.php?page=pmpro-courses-settings', '', 10 );

}
add_action( 'admin_menu', 'pmpro_courses_settings_page' );

function pmpro_courses_settings() {
	require_once PMPRO_COURSES_DIR . '/includes/settings.php';
}

function pmpro_courses_settings_save() {
	if ( isset( $_REQUEST['pmproc_save_integration_settings'] ) ) {
		if ( ! empty( $_REQUEST['pmproc_integrations'] ) ) {
			pmpro_setOption( 'pmproc_integrations', implode( ",", $_REQUEST['pmproc_integrations'] ) );
		} else {
			pmpro_setOption( 'pmproc_integrations', '' );
		}
	}
}
add_action( 'admin_init', 'pmpro_courses_settings_save' );

function pmpro_courses_save_notice() {
	if ( isset( $_REQUEST['pmproc_save_integration_settings'] ) ) {
		echo sprintf( "<div class='updated'><p>%s</p></div>", __( 'Integration Saved Successfully.', 'pmpro-courses') );
	}
}
add_action( 'admin_notices', 'pmpro_courses_save_notice', 10 );

function pmpro_courses_pages( $pages ) {
	$pages['pmpro_my_courses'] = array(
			'label' => __('My Courses Page', 'pmpro-courses'),
			'title' => __('My Courses', 'pmpro-courses'),
			'hint' => __('Include the shortcode [pmpro_my_courses] on the page.', 'pmpro-courses' ),
			'content' => '[pmpro_my_courses]'
		
	);
	$pages['pmpro_all_courses'] = array(
			'label' => __('All Courses Page', 'pmpro-courses'),
			'title' => __('All Courses', 'pmpro-courses'),
			'hint' => __('Include the shortcode [pmpro_all_courses] on the page.', 'pmpro-courses' ),
			'content' => '[pmpro_all_courses]'
		
	);
	return $pages;
}
add_filter( 'pmpro_extra_page_settings', 'pmpro_courses_pages', 10, 1 );

function pmpro_courses_shortcode_courses( $atts ) {

	ob_start();

	$path = dirname(__FILE__);
	$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
	$custom_file = $custom_dir."courses.php";

	$course_limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 5;

	$courses = pmproc_get_courses( $course_limit );

	//load custom or default templates
	if( file_exists($custom_file ) ){
		require_once($custom_file);
	} else {
		require_once($path . "/templates/courses.php");
	}

	$temp_content = ob_get_contents();
	ob_end_clean();

	return $temp_content;

}
add_shortcode( 'pmpro_all_courses', 'pmpro_courses_shortcode_courses' );

function pmpro_my_courses_shortcode_courses( $atts ) {

	ob_start();

	$path = dirname(__FILE__);
	$custom_dir = get_stylesheet_directory()."/paid-memberships-pro/pmpro-courses/";
	$custom_file = $custom_dir."courses.php";

	$course_limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 5;

	$courses = pmproc_get_courses( $course_limit, get_current_user_id() );
	
	//load custom or default templates
	if ( file_exists($custom_file ) ) {
		require_once($custom_file);
	} else {
		require_once($path . "/templates/courses.php");
	}

	$temp_content = ob_get_contents();
	ob_end_clean();

	return $temp_content;

}
add_shortcode( 'pmpro_my_courses', 'pmpro_my_courses_shortcode_courses' );

function pmproc_course_links_my_account() {
	global $pmpro_pages;
	if ( isset( $pmpro_pages['pmpro_my_courses'] ) ) {
		?>
		<li>
			<a href="<?php the_permalink( $pmpro_pages['pmpro_my_courses'] ); ?>"><?php _e('My Courses', 'pmpro-courses'); ?></a>
		</li>
		<?php
	}
}
add_action( 'pmpro_member_links_bottom', 'pmproc_course_links_my_account' );