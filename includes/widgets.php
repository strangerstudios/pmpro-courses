<?php

class PMPRO_Courses_Lesson_Widget extends WP_Widget {

	/**
	 * Sets up the widget
	 */
	public function __construct() {
		parent::__construct(
			'pmpro_courses_lesson_widget',
			'Lessons - PMPro Courses',
			array( 'description' => __('Display related lessons on a single lesson page', 'pmpro-courses') )
		);
	}

	/**
	 * Code that runs on the frontend.
	 *
	 * Modify the content in the <li> tags to
	 * create filter inputs in the sidebar
	 */
	public function widget( $args, $instance ) {
		// If we're not on a page with a PMPro directory, return.
		global $post;

		$user_id = get_current_user_id();

		if ( ! is_a( $post, 'WP_Post' ) || $post->post_type !== 'pmpro_lesson' || ! $user_id ) {
			return;
		}

		$course_id = get_post_meta( $post->ID, 'pmproc_parent', true );		

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		echo $args['before_title'].$title.$args['after_title'];

		$query_args = array(
			'post_type' => 'pmpro_lesson', 
			'posts_per_page' => $instance['lessons_per_page'],
			'meta_query' => array(
				array(
					'key' => 'pmproc_parent',
					'value' => $course_id,
					'compare' => '='
				)
			)
		);

		$progress = get_user_meta( $user_id, 'pmproc_progress_'.$course_id, true );

		$progress = array_unique( $progress );

		$the_query = new WP_Query( $query_args );

		if( $the_query->have_posts() ){
			while( $the_query->have_posts() ){
				$the_query->the_post();

				$lesson_status_icon = '';

				if( in_array( get_the_ID(), $progress ) ){
					$lesson_status_icon = '<span class="dashicons dashicons-visibility" title="'.__('Completed', 'pmpro-courses').'"></span>';
				} else {
					$lesson_status_icon = '<span class="dashicons dashicons-hidden" title="'.__('Pending', 'pmpro-courses').'"></span>';
				}
				?>
				<p><a href='<?php the_permalink(); ?>'><?php the_title(); ?></a><span><?php echo $lesson_status_icon; ?></p>
				<?php

			}
		}

		?>
		<div class="pmpro_courses_lesson_widget_inner">
			<ul>

			</ul>
		</div>
		<?php
		echo $args['after_widget'];
	}

	// Widget Backend 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'Lessons', 'pmpro-courses' );
		}

		if ( isset( $instance[ 'lessons_per_page' ] ) ) {
			$lpp = $instance[ 'lessons_per_page' ];
		} else {
			$lpp = 10;
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'pmpro-courses' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'lessons_per_page' ); ?>"><?php _e( 'Number of Lessons:', 'pmpro-courses' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'lessons_per_page' ); ?>" name="<?php echo $this->get_field_name( 'lessons_per_page' ); ?>" type="number" value="<?php echo esc_attr( $lpp ); ?>" />
		</p>
		<?php 
	}
      
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['lessons_per_page'] = ( ! empty( $new_instance['lessons_per_page'] ) ) ? strip_tags( $new_instance['lessons_per_page'] ) : '';
		return $instance;
	}
 
// Class wpb_widget ends here
} 

/**
 * Check $_REQUEST for parameters from the widget. Add to SQL query.
 */
function my_pmpro_directory_widget_filter_sql_parts( $sql_parts, $levels, $s, $pn, $limit, $start, $end, $order_by, $order ) {
	global $wpdb;

	// Filter results based on membership level if a level was selected.
	if ( ! empty( $_REQUEST['membership_levels'] ) && is_array( $_REQUEST['membership_levels'] ) ) {
		// User's membership level is already joined, so we can skip that step.
		$sql_parts['WHERE'] .= " AND mu.membership_id in ('" . implode( "','", $_REQUEST['membership_levels'] ) . "') ";
	}

	// Filter results based on coat color if a color is selected.
	if ( ! empty( $_REQUEST['coat_color'] ) && is_array( $_REQUEST['coat_color'] ) ) {
		$sql_parts['JOIN'] .= " LEFT JOIN $wpdb->usermeta um_coat_color ON um_coat_color.meta_key = 'dog_coat_color' AND u.ID = um_coat_color.user_id ";
		$sql_parts['WHERE'] .= " AND um_coat_color.meta_value in ('" . implode( "','", $_REQUEST['coat_color'] ) . "') ";
	}

	// Filter results based on max weight if a max weight was inputted.
	if ( ! empty( $_REQUEST['max_weight'] ) && is_numeric( $_REQUEST['max_weight'] ) ) {
		$join_weight = true; // We will JOIN this later, but we don't want to JOIN it twice.
		$sql_parts['WHERE'] .= ' AND um_dog_weight.meta_value <= ' . $_REQUEST['max_weight'] . ' ';
	}
	// Filter results based on min weight if a min weight was inputted.
	if ( ! empty( $_REQUEST['min_weight'] ) && is_numeric( $_REQUEST['min_weight'] ) ) {
		$join_weight = true; // We will JOIN this later, but we don't want to JOIN it twice.
		$sql_parts['WHERE'] .= ' AND um_dog_weight.meta_value >= ' . $_REQUEST['min_weight'] . ' ';
	}
	// Make sure to get the dog weight in the SQL query if we use that in a WHERE clause.
	if ( ! empty( $join_weight ) ) {
		$sql_parts['JOIN'] .= " LEFT JOIN $wpdb->usermeta um_dog_weight ON um_dog_weight.meta_key = 'dog_weight' AND u.ID = um_dog_weight.user_id ";
	}

	return $sql_parts;
}
add_filter( 'pmpro_member_directory_sql_parts', 'my_pmpro_directory_widget_filter_sql_parts', 10, 9 );

/**
 * Registers widget.
 */
function my_pmpro_register_directory_widget() {
	register_widget( 'PMPRO_Courses_Lesson_Widget' );
}
add_action( 'widgets_init', 'my_pmpro_register_directory_widget' );
