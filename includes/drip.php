<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the drip method set on a lesson.
 *
 * @since TBD
 *
 * @param int $lesson_id The lesson ID.
 * @return string Either 'none' or 'date'.
 */
function pmpro_courses_get_lesson_drip_method( $lesson_id ) {
	$drip_method = get_post_meta( $lesson_id, 'pmpro_courses_drip_method', true );

	return 'date' === $drip_method ? 'date' : 'none';
}

/**
 * Get the drip date stored on a lesson.
 * This is the raw stored value and ignores the drip method, so switching a lesson back to
 * no drip does not throw away the date the admin already picked.
 * Use pmpro_courses_is_lesson_released() to decide availability.
 *
 * @since TBD
 *
 * @param int $lesson_id The lesson ID.
 * @return int Unix timestamp in UTC. 0 if no date has been set.
 */
function pmpro_courses_get_lesson_drip_date( $lesson_id ) {
	$drip_date = get_post_meta( $lesson_id, 'pmpro_courses_drip_date_gmt', true );

	return empty( $drip_date ) ? 0 : (int) $drip_date;
}

/**
 * Check whether a lesson is available yet.
 * This is the single place that decides whether a drip method applies at all.
 *
 * @since TBD
 *
 * @param int      $lesson_id The lesson ID.
 * @param int|null $timestamp Unix timestamp in UTC to compare against. Defaults to now.
 * @return bool
 */
function pmpro_courses_is_lesson_released( $lesson_id, $timestamp = null ) {
	// A free lesson is public, so a drip method never applies to it.
	if ( '1' === get_post_meta( $lesson_id, 'pmpro_courses_bypass_restriction', true ) ) {
		return true;
	}

	if ( 'date' !== pmpro_courses_get_lesson_drip_method( $lesson_id ) ) {
		return true;
	}

	$drip_date = pmpro_courses_get_lesson_drip_date( $lesson_id );

	if ( empty( $drip_date ) ) {
		return true;
	}

	if ( null === $timestamp ) {
		$timestamp = time();
	}

	return $timestamp >= $drip_date;
}

/**
 * Get a lesson's drip date formatted in the site timezone.
 *
 * @since TBD
 *
 * @param int $lesson_id The lesson ID.
 * @return string Formatted date/time, or an empty string if no date has been set.
 */
function pmpro_courses_get_lesson_release_label( $lesson_id ) {
	$drip_date = pmpro_courses_get_lesson_drip_date( $lesson_id );

	if ( empty( $drip_date ) ) {
		return '';
	}

	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $drip_date );
}

/**
 * Convert a datetime-local value entered in the site timezone to a UTC timestamp.
 *
 * @since TBD
 *
 * @param string $datetime A datetime string in the site timezone, e.g. 2026-09-01T09:00.
 * @return int Unix timestamp in UTC. 0 if the value is empty or cannot be parsed.
 */
function pmpro_courses_get_timestamp_from_local_datetime( $datetime ) {
	if ( empty( $datetime ) ) {
		return 0;
	}

	try {
		$date = new DateTimeImmutable( $datetime, wp_timezone() );
	} catch ( Exception $e ) {
		return 0;
	}

	return $date->getTimestamp();
}

/**
 * Check whether a user skips the drip date for a lesson.
 * Passing 0 checks nobody, so callers can opt out of the current user fallback.
 *
 * @since TBD
 *
 * @param int      $lesson_id The lesson ID.
 * @param int|null $user_id   The user ID. Defaults to the current user.
 * @return bool
 */
function pmpro_courses_user_can_bypass_release( $lesson_id, $user_id = null ) {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	return user_can( $user_id, 'edit_post', $lesson_id );
}
