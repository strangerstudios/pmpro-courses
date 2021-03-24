<?php
if ( pmpro_courses_is_module_active( 'default' ) ) {
    require_once PMPRO_COURSES_DIR . '/includes/post-types/courses.php';
    require_once PMPRO_COURSES_DIR . '/includes/post-types/lessons.php';
    require_once PMPRO_COURSES_DIR . '/includes/courses.php';
    require_once PMPRO_COURSES_DIR . '/includes/lessons.php';
    require_once PMPRO_COURSES_DIR . '/includes/progress.php';
    require_once PMPRO_COURSES_DIR . '/includes/widgets.php';
    require_once PMPRO_COURSES_DIR . '/includes/shortcodes/all-courses.php';
    require_once PMPRO_COURSES_DIR . '/includes/shortcodes/my-courses.php';
}