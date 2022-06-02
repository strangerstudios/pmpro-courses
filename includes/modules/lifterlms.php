<?php
class PMPro_Courses_LifterLMS extends PMPro_Courses_Module {
	public $slug = 'lifterlms';
	
	/**
	 * Initial setup for the LifterLMS module.
	 * @since 1.0
	 */
	public function init() {		
		add_filter( 'pmpro_courses_modules', array( 'PMPro_Courses_LifterLMS', 'add_module' ), 10, 1 );
	}
	
	/**
     * Initial setup for the LifterLMS module when active.
     * @since 1.0
     */
    public function init_active() {
        add_action('admin_menu', array( 'PMPro_Courses_LifterLMS', 'admin_menu' ), 20);
        
		// If LifterLMS is not active, we're done here.
		if ( ! class_exists( 'LifterLMS' ) ) {
			return;
		}
		
		add_filter( 'pmpro_membership_content_filter', array( 'PMPro_Courses_LifterLMS', 'pmpro_membership_content_filter' ), 10, 2 );
        add_action( 'pmpro_after_all_membership_level_changes', array( 'PMPro_Courses_LifterLMS', 'pmpro_after_all_membership_level_changes' ) );
    }
	
	/**
	 * Add LifterLMS to the modules list.
	 */
	public static function add_module( $modules ){

		$modules[] = array(
			'name' => esc_html__('LifterLMS', 'pmpro-courses'),
			'slug' => 'lifterlms',
			'title' => esc_html__( 'Integrate with the LifterLMS plugin for WordPress.', 'pmpro-courses' ),
			'description' => '<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=plugin&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-lifterlms#lifterlms-module" target="_blank">' . esc_html__( 'Read the LifterLMS Integration documentation &raquo;', 'pmpro-courses' ) . '</a>',
		);
		
		return $modules;
	}
	
	/**
	 * Add Require Membership box to LifterLMS courses.
	 */
	public static function admin_menu() {
		if( function_exists( 'pmpro_page_meta' ) ){
			add_meta_box( 'pmpro_page_meta', esc_html__( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'course', 'side');
		}
	}
		
	
	/**
	 * Override PMPro's the_content filter.
	 * We want to show course content even if it requires membership.
	 * Still showing the non-member text at the bottom.
	 */
	public static function pmpro_membership_content_filter( $filtered_content, $original_content ) {	
		
		if ( is_singular( 'course' ) ) {
			// Show non-member text if needed.
			ob_start();
			// Get hasaccess ourselves so we get level ids and names.
			$hasaccess = pmpro_has_membership_access(NULL, NULL, true);
			if( is_array( $hasaccess ) ) {
				//returned an array to give us the membership level values
				$post_membership_levels_ids = $hasaccess[1];
				$post_membership_levels_names = $hasaccess[2];
				$hasaccess = $hasaccess[0];
				if ( ! $hasaccess ) {
					echo pmpro_get_no_access_message( '', $post_membership_levels_ids, $post_membership_levels_names );
				}
			}
			
			$after_the_content = ob_get_contents();
			ob_end_clean();			
			return $original_content . $after_the_content;		
		} else {
			return $filtered_content;	// Probably false.
		}
	}

	/**
	 * Get courses associated with a level.
	 */
	public static function get_courses_for_levels( $level_ids ) {
		global $wpdb;
		
		// In case a level object was passed in.
		if ( is_object( $level_ids ) ) {
			$level_ids = $level_ids->ID;
		}
		
		// Make sure we have an array of ids.
		if ( ! is_array( $level_ids ) ) {
			$level_ids = array( $level_ids );
		}
		
		if ( empty( $level_ids ) ) {
			return array();
		}
		
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
					SELECT mp.page_id 
					FROM $wpdb->pmpro_memberships_pages mp 
					LEFT JOIN $wpdb->posts p ON mp.page_id = p.ID 
					WHERE mp.membership_id IN(%s) 
					AND p.post_type = 'course' 
					AND p.post_status = 'publish' 
					GROUP BY mp.page_id
				",
				implode(',', $level_ids )
			)
		);
		
		return $course_ids;
	}
	
	/**
	 * When users change levels, enroll/unenroll them from
	 * any associated private courses.
	 */
	public static function pmpro_after_all_membership_level_changes( $pmpro_old_user_levels ) {		
		foreach ( $pmpro_old_user_levels as $user_id => $old_levels ) {
			// Get current courses.
			$current_levels = pmpro_getMembershipLevelsForUser( $user_id );
			if ( ! empty( $current_levels ) ) {
				$current_levels = wp_list_pluck( $current_levels, 'ID' );
			} else {
				$current_levels = array();
			}
			$current_courses = PMPro_Courses_LifterLMS::get_courses_for_levels( $current_levels );
			
			// Get old courses.
			$old_levels = wp_list_pluck( $old_levels, 'ID' );
			$old_courses = PMPro_Courses_LifterLMS::get_courses_for_levels( $old_levels );
			
			// Unenroll the user in any courses they used to have, but lost.
			$courses_to_unenroll = array_diff( $old_courses, $current_courses );
			foreach( $courses_to_unenroll as $course_id ) {
				if ( llms_is_user_enrolled( $user_id, $course_id ) ) {
					// Unenroll student
					llms_unenroll_student( $user_id, $course_id );					
				}
			}
			
			// Enroll the user in any courses for their current levels.
			$courses_to_enroll = array_diff( $current_courses, $old_courses );
			foreach( $courses_to_enroll as $course_id ) {
				if ( ! llms_is_user_enrolled( $user_id, $course_id ) ) {
					llms_enroll_student( $user_id, $course_id );
				}
			}
		}
	}
}
