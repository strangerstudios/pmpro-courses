<?php
/**
 * Parts of code used here has been used from the learndash-paidmemberships plugin
 * and adjusted to suit the needs of this add on.
 */

function pmproc_ld_using_learndash(){

	//Make sure Learndash Integration is active
	if( class_exists( 'Learndash_Paidmemberships' ) ){
		return false; //We should not proceed - conflict
	} else {
		//No conflicts, is LD active
		if( defined( 'LEARNDASH_VERSION' ) ){
			//LD is active
		 	return true;
		}
	}

	return false;
}

/**
 * Get a membership level's associated courses
 * 
 * @param  int    $level ID of a membership level
 * @return array         Courses IDs that belong to a level
 */
function pmproc_ld_get_level_courses( $level ) {
	$courses_levels = get_option( '_level_course_option', array() );

	$courses = array();
	foreach ( $courses_levels as $course_id => $levels ) {
		$levels = explode( ',', $levels );
		if ( in_array( $level, $levels ) ) {
			$courses[] = $course_id;
		}
	}

	return $courses;
}

/**
 * Update course access
 * 
 * @param  int  $level   ID of a membership level
 * @param  int  $user_id ID of WP_User
 * @param  boolean $remove  True to remove course access|false otherwise
 */
function pmproc_ld_update_course_access( $level, $user_id, $remove = false ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	$courses = pmproc_ld_get_level_courses( $level );

	foreach ( $courses as $course_id ) {
		ld_update_course_access( $user_id, $course_id, $remove );
	}
}


/**
 * Get courses that belong to a certain level ID
 * 
 * @param  int    $level_id ID of a level
 * @return array            Array of courses
 */
function pmproc_ld_get_courses_by_level_id( $level_id ) {
	$courses_levels = get_option( '_level_course_option' );

	$courses = array();
	foreach ( $courses_levels as $course_id => $levels ) {
		$levels = explode( ',', $levels );
		if ( in_array( $level_id, $levels ) ) {
			$courses[] = $course_id;
		}
	}

	return $courses;
}

/**
 * Add new course page IDs to pmpro_membership_pages table
 * 
 * @param  int    $membership_id 	ID of PMP membership level
 * @param  int    $course_id        ID of a Learndash course
 * @since  1.0.7
 */
function pmproc_ld_insert_course( $membership_id, $course_id )
{
	global $wpdb;

	$wpdb->insert(
		"{$wpdb->pmpro_memberships_pages}",
		array( 
			'membership_id' => $membership_id,
			'page_id' => $course_id,
		),
		array( '%d', '%d' )
	);
}

/**
 * Delete course page ID from pmpro_membership_pages table
 * 
 * @param  int  $course_id ID of a LearnDash course
 * @since 1.0.7
 */
function pmproc_ld_delete_course_by_course_id( $course_id )
{
	global $wpdb;

	$wpdb->delete(
		"{$wpdb->pmpro_memberships_pages}",
		array( 'page_id' => $course_id ),
		array( '%d' )
	);
}

/**
 * Delete course page ID from pmpro_membership_pages table
 * 
 * @param  int  $course_id ID of a LearnDash course
 * @since 1.0.7
 */
function pmproc_ld_delete_course_by_membership_id_course_id( $membership_id, $course_id )
{
	global $wpdb;

	$wpdb->delete(
		"{$wpdb->pmpro_memberships_pages}",
		array( 'membership_id' => $membership_id, 'page_id' => $course_id ),
		array( '%d', '%d' )
	);
}

function pmproc_ld_generate_access_list($course_id, $levels){
	global $wpdb;
	$levels_sql = implode(',', $levels);
	$users = $wpdb->get_results("SELECT * FROM {$wpdb->pmpro_memberships_users} WHERE membership_id IN ($levels_sql) AND status='active'");
	$user_ids = array();
	foreach($users as $user){
		$user_ids[] = $user->user_id;			
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	pmproc_ld_reassign_access_list($course_id, $user_ids);
}

function pmproc_ld_reassign_access_list($course_id, $access_list) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	$old_access_list = explode(",", $meta['sfwd-courses_course_access_list']);
	foreach ($access_list as $user_id) {
		if(!in_array($user_id, $old_access_list))
			ld_update_course_access($user_id, $course_id); //Add user who was not in old list
	}
	foreach ($old_access_list as $user_id) {
		if(!in_array($user_id, $access_list))
			ld_update_course_access($user_id, $course_id, true); //Remove user who was in old list but not in new list
	}
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );

	$level_course_option = get_option('_level_course_option');	
	if(!empty($level_course_option[$course_id]))
		$meta['sfwd-courses_course_price_type'] = 'closed';

//		$meta['sfwd-courses_course_price'] = 'Membership';
	update_post_meta( $course_id, '_sfwd-courses', $meta );
}

function pmproc_ld_require_membership(){

	add_meta_box("pmproc-ld-level-list-meta", __("Require Membership","pmpro-courses"), "pmproc_ld_level_list", "sfwd-courses", "side", "low");

}
add_action( 'admin_init', 'pmproc_ld_require_membership' );

function pmproc_ld_level_list(){
	global $post;
	global $wpdb;
	global $membership_levels;
	if(!isset($wpdb->pmpro_membership_levels))
	{
		_e("Please enable Paid Memberships Pro Plugin, and create some levels", 'learndash-paidmemberships');
		return;
	}	
	$membership_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	$course_id = learndash_get_course_id($post->ID);
	$level_course_option = get_option('_level_course_option');
	$array_levels = explode(",",$level_course_option[$course_id]);

	wp_nonce_field( 'ld_pmpro_save_metabox', 'pmproc_ld_nonce' );

	for($num_cursos=0;$num_cursos<sizeof($membership_levels);$num_cursos++)
	{
		$checked="";
		for($tmp_array_levels=0;$tmp_array_levels<sizeof($array_levels);$tmp_array_levels++){
			if($array_levels[$tmp_array_levels] == $membership_levels[$num_cursos]->id){	
				$checked="checked";
			}
		}
		?>
		<p><input type="checkbox" name="level-curso[<?php echo $num_cursos ?>]" value="<?php echo $membership_levels[$num_cursos]->id; ?>" <?php echo $checked; ?>> <?php echo $membership_levels[$num_cursos]->name; ?></p>
		<?php
	}

}

function pmproc_ld_save_post( $post_id, $post, $update ){

	global $table_prefix, $wpdb;
	// var_dump(current_user_can( 'publish_posts' ) );
	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}

	if ( isset( $_REQUEST['pmproc_ld_nonce'] ) && ! wp_verify_nonce( $_REQUEST['pmproc_ld_nonce'], 'ld_pmpro_save_metabox' ) ) {
		return;
	}

	$course_id = learndash_get_course_id( $post_id );
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	$current_access_list = isset( $meta['sfwd-courses_course_access_list'] ) ? $meta['sfwd-courses_course_access_list'] : "";
	$current_access_list = explode( ',', $current_access_list );
	$level_course_option = get_option('_level_course_option');

	if ( isset( $_POST["level-curso"] ) && is_array( $_POST["level-curso"] ) ) {

		$access_list = array();
		$levels_list = array();

		// Delete old course page ID from pmpro_membership_pages table
		pmproc_ld_delete_course_by_course_id( $post_id );

		$tmp_levels_list=0;
		foreach ($_POST["level-curso"] as $x) {
			$users_pro_list = $wpdb->get_results("SELECT * FROM {$wpdb->pmpro_memberships_users} WHERE membership_id = '$x' AND status='active'", ARRAY_N);

			// Add new course page IDs to pmpro_membership_pages table
			pmproc_ld_insert_course( $x, $post_id );

			foreach ($users_pro_list as $user_pro){
				$access_list[] = $user_pro[1];			
			}
			$levels_list[] .= $x;			
		}

		$levels_list_tmp = implode(',',$levels_list);
		$level_course_option[$course_id] = $levels_list_tmp;

		$access_list = array_merge( $current_access_list, $access_list );

		pmproc_ld_reassign_access_list($course_id, $access_list);			
	} else {
		// Delete old course page ID from pmpro_membership_pages table
		pmproc_ld_delete_course_by_course_id( $post_id );

		$level_course_option[$course_id] = '';
	}

	update_option("_level_course_option", $level_course_option);

}
add_action( 'save_post', 'pmproc_ld_save_post', 10, 3 );

function pmproc_ld_after_level_settings(){

	if( !pmproc_ld_using_learndash() ){
		return __('Please deactivate Learndash for Paid Memberships Pro to prevent any conflicts with Paid Memberships Pro Courses - Learndash', 'pmpro-courses' );
	}

	global $wpdb;
		?>		
	<h3 class="topborder"><?php _e( 'Paid Memberships Pro Courses - LearnDash', 'learndash-paidmemberships' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Courses', 'pmpro-courses' );?>:</label></th>
				<td>
					<ul>
						<?php
						$querystr = "SELECT wposts.* FROM $wpdb->posts wposts WHERE wposts.post_type = 'sfwd-courses' AND wposts.post_status = 'publish' ORDER BY wposts.post_title";
						
						$actual_level = $_REQUEST['edit'];
						$level_course_option = get_option('_level_course_option');
						
						$results = $wpdb->get_results($querystr, OBJECT);

						if( $results ) {
							$count = 0;
							foreach( $results as $s ) {
								$checked = '';
								$tmp_levels_course = explode(",",@$level_course_option[$s->ID]);
								if(in_array($actual_level, $tmp_levels_course)){
									$checked = 'checked';
								}
								?>
								<li><input type="checkbox" name="cursos[<?php echo $count; ?>]" value="<?php echo $s->ID ?>" <?php echo $checked; ?> id='<?php echo $s->post_title; ?>'> <label for='<?php echo $s->post_title; ?>'><?php echo $s->post_title; ?></label></li>
								<?php
								$count++;
							}

						}
						?>
					</ul>
				</td>
			</tr>
		</tbody>
	</table>
	<?php

}
add_action( 'pmpro_membership_level_after_other_settings', 'pmproc_ld_after_level_settings' );

function pmproc_ld_save_level_settings(){

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	global $wpdb;

	$users_pro_list = $wpdb->get_results("SELECT * FROM {$wpdb->pmpro_memberships_users} WHERE membership_id = '$saveid' AND status='active'", ARRAY_N);
	
	$new_courses = $_REQUEST['cursos'] ? $_REQUEST['cursos'] : array();

	$courses = get_posts(array(
		'post_type' => 'sfwd-courses',
		'post_status' => 'publish',
		'posts_per_page'   => -1
	));

	$courses_levels = get_option('_level_course_option');

	foreach($courses as $course){
		$refresh = false;
		$levels = @$courses_levels[$course->ID] ? explode(',', @$courses_levels[$course->ID]) : array();

		//If the course is in the level and it wasn't add it
		if(array_search($course->ID, $new_courses) !== FALSE && array_search($saveid, $levels) === FALSE){
			$refresh = true;
			$levels[] = $saveid;
			$courses_levels[$course->ID] = implode(',', $levels);

			pmproc_ld_insert_course( $saveid, $course->ID );
		}

		// When the course is not in the level but it was
		else if(array_search($course->ID, $new_courses) === FALSE && array_search($saveid, $levels) !== FALSE){				
			$refresh = true;
			$level_index = array_search($saveid, $levels);
			unset($levels[$level_index]);
			$courses_levels[$course->ID] = implode(',', $levels);

			pmproc_ld_delete_course_by_membership_id_course_id( $saveid, $course->ID );
		}

		if($refresh){
			pmproc_ld_generate_access_list($course->ID, $levels);
		}
	}

	update_option("_level_course_option",$courses_levels);

}
add_action( 'pmpro_save_membership_level', 'pmproc_ld_save_level_details' );

function pmproc_ld_user_change_level( $level, $user_id, $cancel_level ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	// Add approval check if PMPro approval addon is active
	if ( class_exists( 'PMPro_Approvals' ) ) {
		if ( PMPro_Approvals::requiresApproval( $level ) && ! PMPro_Approvals::isApproved( $user_id, $level ) ) {
			return;
		}
	}

	$all_levels    = pmpro_getAllLevels();
	$active_levels = pmpro_getMembershipLevelsForUser( $user_id );

	$active_levels_ids = array();
	if ( is_array( $active_levels ) ) {
		foreach ( $active_levels as $active_level ) {
			$active_levels_ids[] = $active_level->id;
		}
	}

	if ( is_array( $all_levels ) ) {
		foreach ( $all_levels as $all_level ) {
			if ( in_array( $all_level->id, $active_levels_ids ) ) {
				continue;
			}

			pmproc_ld_update_course_access( $all_level->id, $user_id, $remove = true );	
		}
	}

	foreach ( $active_levels_ids as $active_level_id ) {
		// enroll users
		pmproc_ld_update_course_access( $active_level_id, $user_id );	
	}
}
add_action( 'pmpro_after_change_membership_level', 'pmproc_ld_user_change_level', 10, 3 );

/**
 * Update user course access on approval (requires approval add-on)
 * 
 * @param  int    $meta_id    ID of meta key
 * @param  int    $object_id  ID of a WP_User
 * @param  string $meta_key   Meta key
 * @param  string $meta_value Meta value
 */
function pmproc_ld_update_access_on_approval( $meta_id, $object_id, $meta_key, $meta_value ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	preg_match( '/pmpro_approval_(\d+)/', $meta_key, $matches );

	if ( isset( $matches[0] ) && false !== strpos( $matches[0], 'pmpro_approval' ) ) {
		$level = $matches[1];
		if ( 'approved' == $meta_value['status'] ) {
			pmproc_ld_update_course_access( $level, $object_id );
		} else {
			pmproc_ld_update_course_access( $level, $object_id, $remove = true );
		}
	}
}
add_action( 'update_user_meta', 'pmproc_ld_update_access_on_approval', 10, 4 );

/**
 * Update course access when order is updated
 * 
 * @param  object $order Object of an order
 */
function pmproc_ld_update_course_access_on_order_update( $order ) {	

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	switch ( $order->status ) {
		case 'success':
			pmproc_ld_give_course_access_by_order( $order );
			break;
		
		case 'cancelled':
		case 'error':
		case 'pending':
		case 'refunded':
		case 'review':
			pmproc_ld_remove_course_access_by_order( $order );
			break;
	}
}
add_action( 'pmpro_updated_order', 'pmproc_ld_update_course_access_on_order_update', 10, 1 );

/**
 * Remove user course access when an order is deleted
 * 
 * @param  int    $order_id ID of an order
 * @param  object $order    Order object
 */
function pmproc_ld_remove_course_access_on_order_deletion( $order_id, $order ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	$level    = $order->getMembershipLevel();
	$user     = $order->getUser();
	$courses  = pmproc_ld_get_courses_by_level_id( $level->id );
	
	if ( is_array( $courses ) && is_object( $user ) ) {
		foreach ( $courses as $course_id ) {
			ld_update_course_access( $user->ID, $course_id, true );
		}
	}
}
add_action( 'pmpro_delete_order', 'pmproc_ld_remove_course_access_on_order_deletion', 10, 2 );

/**
 * Remove course access by given order
 * 
 * @param  object $order Order object
 */
function pmproc_ld_remove_course_access_by_order( $order ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	$level    = $order->getMembershipLevel();
	$user     = $order->getUser();
	$courses  = pmproc_ld_get_courses_by_level_id( $level->id );
	
	if ( is_array( $courses ) && is_object( $user ) ) {
		foreach ( $courses as $course_id ) {
			ld_update_course_access( $user->ID, $course_id, true );
		}
	}
}
add_action( 'pmpro_subscription_expired', 'pmproc_ld_remove_course_access_by_order', 10, 1 );
add_action( 'pmpro_subscription_cancelled', 'pmproc_ld_remove_course_access_by_order', 10, 1 );
add_action( 'pmpro_subscription_recuring_stopped', 'pmproc_ld_remove_course_access_by_order', 10, 1 );

/**
 * Give course access by given order
 *
 * @param object $order Order object
 */
function pmproc_ld_give_course_access_by_order( $order ) {

	if( !pmproc_ld_using_learndash() ){
		return;
	}

	$level = $order->getMembershipLevel();
	$user  = $order->getUser();

	$courses = pmproc_ld_get_courses_by_level_id( $level->id );
	if ( is_array( $courses ) && is_object( $user ) ) {
		foreach ( $courses as $course_id ) { 
			ld_update_course_access( $user->ID, $course_id, false );
		}
	}
}
add_action( 'pmpro_subscription_recuring_restarted', 'pmproc_ld_give_course_access_by_order', 10, 1 );

/**
 * Give user course access if he already has access to a particular course even though he's not a member of the course's membership
 *
 * @param bool  $hasaccess Whether user has access or not
 * @param int   $mypost Course WP_Post
 * @param int   $myuser WP_User
 * @param array $mypost List of membership levels that protect this course
 * @return boolean Returned $hasaccess
 */
function pmproc_ld_has_course_access( $hasaccess, $mypost, $myuser, $post_membership_levels ) {

	if( !pmproc_ld_using_learndash() ){
		return $hasaccess;
	}

	if ( 'sfwd-courses' == $mypost->post_type ) {
		$hasaccess = true;
	}

	return $hasaccess;
}
add_filter( 'pmpro_has_membership_access_filter', 'pmproc_ld_has_course_access', 99, 4 );