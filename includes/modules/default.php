<?php
class PMPro_Courses_Module {
    // Single instance of this class.
    protected static $_instance = null;
    
    // Slug for this module.
    public $slug = 'default';
    
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
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     */
    final public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'pmpro-courses' ), '4.6' );
        die();
    }
    
    /**
     * Run init if active.
     * @since 1.0
     */
    public function __construct() {
        $this->init();
        
        if ( $this->is_active() ) {
            $this->init_active();
        }

        // Hooks we always want to run.
        add_action( 'pmpro_courses_module_settings', array( 'PMPro_Courses_Module', 'admin_settings' ) );
        add_action( 'pmpro_courses_settings_save', array( 'PMPro_Courses_Module', 'admin_settings_save' ) );
    }
    
    /**
     * Check if this module is checked as active in the settings.
     * @since 1.0
	 * @return bool Whether or not the module is active.
     */
    public function is_active() {
        return pmpro_courses_is_module_active( $this->slug );
    }
    
    /** Methods below here should be overidden in your extended class. **/
    
    /**
     * Initial setup for the default module.
     * Override this for modules that inherit this class.
     * This code will run if the module is active or not.
     * @since 1.0
     */
    public function init() {
        // example to use hook to add your module
        // add_filter( 'pmpro_courses_modules', array( 'PMPro_Courses_MyModule', 'add_module' ), 10, 1 );
    }
    
    /**
	 * Example callback to add your module to the modules list.
	 */
    /*
	public static function add_module( $modules ){

		$modules[] = array(
			'name' => __('MyModule', 'pmpro-courses'),
			'slug' => 'mymodule',
			'description' => __( 'My Module', 'pmpro-courses' ),
		);
		
		return $modules;
	}
    */
    
    /**
     * Initial setup for the default module when active.
     * Override this for modules that inherit this class.
     * @since 1.0
     */
    public function init_active() {
        require_once PMPRO_COURSES_DIR . '/includes/post-types/courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/post-types/lessons.php';
        require_once PMPRO_COURSES_DIR . '/includes/courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/lessons.php';
        require_once PMPRO_COURSES_DIR . '/includes/progress.php';
        require_once PMPRO_COURSES_DIR . '/includes/shortcodes/all-courses.php';
        require_once PMPRO_COURSES_DIR . '/includes/shortcodes/my-courses.php';
                
        add_filter( 'pmpro_membership_content_filter', 'pmpro_courses_show_course_content_and_lessons', 10, 2 );
        add_filter( 'the_content', 'pmpro_courses_add_lessons_to_course' );
    }

    /**
     * Save additional admin settings.
     * @since 1.2.2
     */
    static public function admin_settings_save() {
        // Save settings.
        if ( ! empty( $_REQUEST['pmpro_courses_cpt_archive'] ) ) {
            update_option( 'pmpro_courses_cpt_archive', 1 );
        } else {
            update_option( 'pmpro_courses_cpt_archive', 0 );
        }
    }

    /**
     * Additional admin settings.
     * @since 1.2.2
     */
    static public function admin_settings( $module ) {
        if ( empty( $module ) || $module['slug'] !== 'default' ) {
            return;
        }

        // Get settings from options.
        $cpt_archive = get_option( 'pmpro_courses_cpt_archive', 1 );

        // Show settings.
        ?>
        <div class="pmpro-courses-module-settings pmpro-courses-default-settings" data-module="default">
            <input type='checkbox' name='pmpro_courses_cpt_archive' value='1' id='pmpro_courses_cpt_archive' <?php checked( $cpt_archive, 1 ); ?> />
            <label for="pmpro_courses_cpt_archive"><?php esc_html_e( 'Enable Archive Page for the Default Courses CPT?', 'pmpro-courses' );?></label>
        </div>
        <?php
    }
}
