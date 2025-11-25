<?php

/**
 * Register Custom Post Type for lessons
 * Hooks into init.
 */
function pmpro_courses_lesson_cpt() {

	$labels  = array(
		'name'                  => esc_html_x( 'Lessons', 'Post Type General Name', 'pmpro-courses' ),
		'singular_name'         => esc_html_x( 'Lesson', 'Post Type Singular Name', 'pmpro-courses' ),
		'menu_name'             => esc_html__( 'Lessons', 'pmpro-courses' ),
		'name_admin_bar'        => esc_html__( 'Lesson', 'pmpro-courses' ),
		'archives'              => esc_html__( 'Lesson Archives', 'pmpro-courses' ),
		'attributes'            => esc_html__( 'Lesson Attributes', 'pmpro-courses' ),
		'all_items'             => esc_html__( 'All Lessons', 'pmpro-courses' ),
		'add_new_item'          => esc_html__( 'Add New Lesson', 'pmpro-courses' ),
		'add_new'               => esc_html__( 'Add New Lesson', 'pmpro-courses' ),
		'new_item'              => esc_html__( 'New Lesson', 'pmpro-courses' ),
		'edit_item'             => esc_html__( 'Edit Lesson', 'pmpro-courses' ),
		'update_item'           => esc_html__( 'Update Lesson', 'pmpro-courses' ),
		'view_item'             => esc_html__( 'View Lesson', 'pmpro-courses' ),
		'view_items'            => esc_html__( 'View Lessons', 'pmpro-courses' ),
		'search_items'          => esc_html__( 'Search Lesson', 'pmpro-courses' ),
		'not_found'             => esc_html__( 'Lesson not found', 'pmpro-courses' ),
		'not_found_in_trash'    => esc_html__( 'Lesson not found in Trash', 'pmpro-courses' ),
		'featured_image'        => esc_html__( 'Featured Image', 'pmpro-courses' ),
		'set_featured_image'    => esc_html__( 'Set lesson featured image', 'pmpro-courses' ),
		'remove_featured_image' => esc_html__( 'Remove featured image', 'pmpro-courses' ),
		'use_featured_image'    => esc_html__( 'Use as lesson featured image', 'pmpro-courses' ),
		'insert_into_item'      => esc_html__( 'Insert into lesson', 'pmpro-courses' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this lesson', 'pmpro-courses' ),
		'items_list'            => esc_html__( 'PMPro Lessons list', 'pmpro-courses' ),
		'items_list_navigation' => esc_html__( 'Lessons list navigation', 'pmpro-courses' ),
		'filter_items_list'     => esc_html__( 'Filter Lessons list', 'pmpro-courses' ),
	);
	$rewrite = array(
		'slug'       => pmpro_courses_unique_rewrite_slug( 'lesson' ),
		'with_front' => true,
		'pages'      => true,
		'feeds'      => false,
	);
	$args    = array(
		'label'               => esc_html__( 'Lesson', 'pmpro-courses' ),
		'description'         => esc_html__( 'Lessons for Paid Memberships Pro Courses', 'pmpro-courses' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=pmpro_course',
		'menu_position'       => 1,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'show_in_rest'        => true,
	);
	register_post_type( 'pmpro_lesson', $args );

}
add_action( 'init', 'pmpro_courses_lesson_cpt' );

/**
 * Add meta boxes for lessons.
 */
function pmpro_courses_lessons_cpt_define_meta_boxes() {
	add_meta_box( 'pmpro_courses_lesson_settings', esc_html__( 'Lesson Settings', 'pmpro-courses' ), 'pmpro_courses_lesson_course_metabox', 'pmpro_lesson', 'normal' );
}
add_action('admin_menu', 'pmpro_courses_lessons_cpt_define_meta_boxes', 20);

/**
 * Lesson settings meta box.
 */
function pmpro_courses_lesson_course_metabox( $post ) {	
	wp_nonce_field( 'pmpro_courses_metabox_nonce', 'pmpro_courses_metabox_nonce' );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="components-base-control__label" for="pmpro_courses_parent"><?php esc_html_e( 'Course', 'pmpro-courses' );?></label></th>
				<td>
					<?php
						wp_dropdown_pages( array(
							'selected' => $post->post_parent,
							'echo' => 1,
							'show_option_none' => '-- ' . esc_html__( 'None', 'pmpro-courses' ) . ' --',
							'name' => 'pmpro_courses_parent',
							'id' => 'pmpro_courses_parent',
							'post_type' => 'pmpro_course',
							'post_status' => 'publish',
						) );
					?>
				</td>
			</tr>
			<tr>
				<th><label class="components-base-control__label" for="pmpro_courses_bypass_restriction"><?php esc_html_e( 'Free Lesson', 'pmpro-courses' );?></label></th>
				<td>
					<?php
						$bypass_lesson = get_post_meta( $post->ID, 'pmpro_courses_bypass_restriction', true );
					?>
					<label>
						<input type="checkbox" name="pmpro_courses_bypass_restriction" value="1" <?php checked( $bypass_lesson, '1' ); ?> />
						<?php esc_html_e( 'Yes, bypass member restrictions and make this lesson public.', 'pmpro-courses' ); ?>
					</label>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Save meta data for lessons from the edit lesson page.
 */
function pmpro_courses_save_lessons_meta( $post_id, $post, $update ) {

	// Autosave, Bail
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Permissions check that the person saving can actually save this post.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Check the nonce and make sure it's valid.
	if ( ! isset ( $_POST['pmpro_courses_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['pmpro_courses_metabox_nonce'], 'pmpro_courses_metabox_nonce' ) ) {
		return;
	}

	// Save the post parent custom metabox if it's set and not currently set to via page attributes.
	if ( isset( $_REQUEST['pmpro_courses_parent'] ) && intval( $_REQUEST['pmpro_courses_parent'] ) !== $post->post_parent ) {
		wp_update_post(
			array( 
				'ID' => $post_id,
				'post_parent' => intval( $_REQUEST['pmpro_courses_parent'] )
			)
		);	
	}

	// Update the bypass member restriction logic when saving.
	$bypass = isset( $_POST['pmpro_courses_bypass_restriction'] ) ? '1' : '';
	update_post_meta( $post_id, 'pmpro_courses_bypass_restriction', $bypass );

}
add_action( 'save_post_pmpro_lesson', 'pmpro_courses_save_lessons_meta', 10, 3 );