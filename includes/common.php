<?php

function pmpro_courses_get_lessons( $course = 0 ){

	global $wpdb;

	$sql = "SELECT * FROM $wpdb->posts ";

	if( $course !== 0 ){
		$sql .= " LEFT JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id 
		WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->postmeta.meta_key = 'pmproc_parent' AND $wpdb->postmeta.meta_value = '$course' ";
	} else {
		$sql .= "WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish' ";
	}

	$results = $wpdb->get_results( $sql );

	$lessons = array();

	if( !empty( $results ) ){

		foreach( $results as $result ){
			$lessons[] = array(
				'id' 	=> $result->ID,
				'title' => $result->post_title,
				'content' => $result->post_content,
				'excerpt' => $result->post_excerpt,
				'permalink' => get_the_permalink( $result->ID )
			);

		}

	}
	
	return $lessons;

}

function pmpro_courses_get_course( $lesson_id ){

	$parent = intval( get_post_meta( $lesson_id, 'pmproc_parent', true ) );

	if( $parent ){

		$course = get_post( $parent );

		if( $course ){

			return "<a href='".admin_url( 'post.php?post='.$parent.'&action=edit' )."'>".$course->post_title."</a>";

		}
	}

	return __('Lesson Not Assigned', 'pmpro-courses');

}

function pmpro_courses_build_lesson_html( $array_content ){

	$ret = "";

	if( !empty( $array_content ) ){
		
		$count = 1;

		foreach ( $array_content as $lesson ) {

			$ret .= "<tr>";
			$ret .= "<td>".$count."</td>";
			$ret .= "<td><a href='".admin_url( 'post.php?post='.$lesson['id'].'&action=edit' )."' title='".__('Edit', 'pmpro-courses').' '.$lesson['title']."' target='_BLANK'>".$lesson['title']."</a></td>";
			$ret .= "<td>";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_editPost(".$lesson['id']."); void(0);'>".__( 'edit', 'pmpro-series' )."</a>";
			$ret .= "<a class='button button-secondary' href='javascript:pmproc_removePost(".$lesson['id']."); void(0);'>".__( 'remove', 'pmpro-series' )."</a>";
			$ret .= "</td>";
			$ret .= "</tr>";

			$count++;
		}

	} 

	return $ret;

}

function pmpro_courses_get_lesson_count( $course ){

	global $wpdb;

	$sql = "SELECT count(*) FROM $wpdb->posts ";

	$sql .= " LEFT JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id 
		WHERE $wpdb->posts.post_type = 'pmpro_lesson' AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->postmeta.meta_key = 'pmproc_parent' AND $wpdb->postmeta.meta_value = '$course' ";

	$results = $wpdb->get_var( $sql );

	return $results;

}

function pmpro_courses_check_level( $post_id ){

	global $wpdb;

	if( is_singular( array( 'pmpro_lesson' ) ) ){

		$parent = intval( get_post_meta( $post_id, 'pmproc_parent', true ) );

		$overrides = get_post_meta( $post_id, 'pmproc_lesson_override', true );

		//We're overriding the membership level in the lesson
		if( $overrides !== '' && $overrides !== null ){
			if(  !pmpro_hasMembershipLevel( $overrides ) ){
				return false;
			} else {
				return true;
			}
		} 

		if( $parent !== '' ){

			$required_membership = array();
			$sql = "SELECT * FROM $wpdb->pmpro_memberships_pages WHERE `page_id` = ".$parent."";
			$results = $wpdb->get_results( $sql  );
			if( !empty( $results ) ){
				foreach( $results as $result ){
					$required_membership[] = intval( $result->membership_id );
				}
				if( !pmpro_hasMembershipLevel( $required_membership ) ){
					return false;
				} else {
					return true;
				}
			}

		}

	}

	if( is_singular( array( 'pmpro_course' ) ) ){
		$required_membership = array();
		$sql = "SELECT * FROM $wpdb->pmpro_memberships_pages WHERE `page_id` = ".$post_id."";
		$results = $wpdb->get_results( $sql  );
		if( !empty( $results ) ){
			foreach( $results as $result ){
				$required_membership[] = intval( $result->membership_id );
			}			
			if( !pmpro_hasMembershipLevel( $required_membership ) ){
				return false;
			} else {
				return true;
			}
		}
	}

}

function pmproc_complete_button( $lid, $cid ){

	$button_text = apply_filters( 'pmproc_button_to_complete_text', __('Mark As Complete', 'pmpro-courses') );

	$complete_text = apply_filters( 'pmproc_button_complete_text', __('Completed', 'pmpro-courses' ) );

	$content = "<span><button class='pmproc_button_mark_complete_action ".pmpro_get_element_class('span_pmpro_checkout_button pmproc_button_mark_complete')."' lid='".$lid."' cid='".$cid."'>".$button_text."</button></span>";

	$user = wp_get_current_user();

	if( $user ){

		$user_id = $user->ID;

		$progress = get_user_meta( $user_id, 'pmproc_progress_'.$cid, true );

		if( !empty( $progress ) ){

			if( in_array( $lid, $progress ) ){

				$show_complete_button = apply_filters( 'pmproc_button_show_complete', true );

				if( $show_complete_button ){
				
					$content = "<span><button class='".pmpro_get_element_class('span_pmpro_checkout_button pmproc_button_mark_complete')."'>".$complete_text."</button></span>";
				}
			}
		}
	}

	return $content;

}

function pmproc_get_user_progress( $course_id ){

	$percentage = 0;

	$lesson_count = pmpro_courses_get_lesson_count( $course_id );

	$progress = count( pmproc_get_complete_lessons( $course_id ) );

	if( $lesson_count !== 0 && $progress !== 0 ){
	
		$percentage = $progress / $lesson_count * 100;

	}

	return $percentage;

}

function pmproc_display_progress_bar( $course_id ){

	$percentage = pmproc_get_user_progress( $course_id );

	if( $percentage !== 0 ){

		return '<div><div data-preset="line" class="ldBar" data-value="'.$percentage.'" style="width: 	100%;"></div></div>';

	} 

	return;

}

function pmproc_get_complete_lessons( $cid ){

	$user = wp_get_current_user();

	$progress = array();

	if( $user ){

		$user_id = $user->ID;

		$progress = get_user_meta( $user_id, 'pmproc_progress_'.$cid, true );	

	}

	return array_unique( $progress );

}