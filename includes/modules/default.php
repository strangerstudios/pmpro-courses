<?php
class PMPro_Courses_Module {
    // Single instance of this class.
    protected static $_instance = null;
    
    /**
	 * Get the one instance of this class.
	 * @since 1.0
	 * @return PMPro_Courses_Module Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    
    /**
     * Run init if active.
     * @since 1.0
     */
    function __construct() {
        if ( $this->is_active() ) {
            $this->init();
        }
    }
    
    /**
     * Check if this module is checked as active in the settings.
     * @since 1.0
	 * @return bool Whether or not the module is active.
     */
    function is_active() {
        return pmpro_courses_is_module_active( 'default' );
    }
    
    /**
     * Initial setup for the default module.
     * Override this for modules that inherit this class.
     * @since 1.0
     */
    function init() {
        require_once PMPRO_COURSES_DIR . '/includes/post-types/courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/post-types/lessons.php';
        require_once PMPRO_COURSES_DIR . '/includes/courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/lessons.php';
        require_once PMPRO_COURSES_DIR . '/includes/progress.php';
        require_once PMPRO_COURSES_DIR . '/includes/widgets.php';
        require_once PMPRO_COURSES_DIR . '/includes/shortcodes/all-courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/shortcodes/my-courses.php';
    }
}
