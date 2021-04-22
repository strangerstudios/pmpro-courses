<?php
class PMPro_Courses_LearnDash extends PMPro_Courses_Module {
	public $slug = 'learndash';
	
	/**
	 * Initial setup for the LearnDash module.
	 * @since 1.0
	 */
	public function init() {		
		add_filter( 'pmpro_courses_modules', array( 'PMPro_Courses_LearnDash', 'add_module' ), 10, 1 );
	}
	
	/**
     * Initial setup for the LearnDash module when active.
     * @since 1.0
     */
    public function init_active() {
        add_action('admin_menu', array( 'PMPro_Courses_LearnDash', 'admin_menu' ), 20);
		add_action( 'template_redirect', array( 'PMPro_Courses_LearnDash', 'template_redirect' ) );
        add_filter( 'pmpro_membership_content_filter', array( 'PMPro_Courses_LearnDash', 'pmpro_membership_content_filter' ), 10, 2 );
    }
	
	/**
	 * Add LearnDash to the modules list.
	 */
	public static function add_module( $modules ){

		$modules[] = array(
			'name' => __('LearnDash', 'pmpro-courses'),
			'slug' => 'learndash',
			'description' => __( 'LearnDash LMS', 'pmpro-courses' ),
		);
		
		return $modules;
	}
	
	/**
	 * Add Require Membership box to LearnDash courses.
	 */
	public static function admin_menu() {
		add_meta_box( 'pmpro_page_meta', __( 'Require Membership', 'pmpro-courses' ), 'pmpro_page_meta', 'sfwd-courses', 'side');
	}
	
	/**
	 * If a course requires membership, redirect its lessons
	 * to the main course page.
	 */
	public static function template_redirect() {		
		global $post, $pmpro_pages;

		if( ! empty( $post ) && is_singular() ) {		
			// Only check if a LearnDash CPT.
			$ld_cpts = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-question', 'sfwd-certificates', 'groups', 'sfwd-assignment' );
			if ( ! in_array( $post->post_type, $ld_cpts ) ) {
				return;
			}
			
			// Let admins in.
			if ( current_user_can( 'manage_options' ) ) {
				return;
			}		
			
			// Okay check access.
			// TODO handle non courses. Check the related course. Maybe filter pmpro_has_membership_access instead of adding logic here.
			$access = pmpro_has_membership_access( $post->ID );
			
			// They have access. Let em in.
			if ( $access ) {
				return;
			}
			
			// Make sure we don't redirect away from the levels page if they have odd settings.
			if ( intval( $pmpro_pages['levels'] ) == $post->ID ) {
				return;
			}
			
			// No access.
			if ( $post->post_type == 'sfwd-courses' ) {
				// Don't redirect courses unless a url is passed in filter.
				$redirect_to = apply_filters( 'pmpro_courses_course_redirect_to', null );				
			} else {
				// Send lessons and other content to the parent course.
				$redirect_to = apply_filters( 'pmpro_courses_lesson_redirect_to', get_permalink( $post->post_parent ) );
			}
			
			if ( $redirect_to ) {
				wp_redirect( $redirect_to );
				exit;
			}	
		}
	}
	
	/**
	 * Override PMPro's the_content filter.
	 * We want to show course content even if it requires membership.
	 * Still showing the non-member text at the bottom.
	 */
	public static function pmpro_membership_content_filter( $filtered_content, $original_content ) {		
		if ( is_singular( 'sfwd-courses' ) ) {
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
}