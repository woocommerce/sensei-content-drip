<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Content Drip Extension Utilities Class
 *
 * Common functions used by the Content drip extension
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Utilities
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 * - get_dripping_lessons_by_type
 * - get_course_users
 *
 */
class Scd_Ext_Utils {
	/**
	 * Returns all the lesson with passed in drip type
	 *
	 * @return array
	 */
	public function get_dripping_lessons_by_type( $type ) {
		// Setup the return value
		$dripping_lesson_ids = array();

		if ( empty( $type ) ) {
			return $dripping_lesson_ids;
		}

		// If type none return all lessons with no meta query
		if ( 'none' === $type ) {
			$meta_query = array();
		} else {
			$meta_query = array(
				array(
					'key'   => '_sensei_content_drip_type',
					'value' => sanitize_key( $type ),
				),
			);
		}

		// Create the lesson query args
		$lesson_query_args = array(
			'post_type'      => 'lesson',
			'posts_per_page' => 500,
			'meta_query'     => $meta_query,
		);

		// Fetch all posts matching the arguments
		$lesson_objects = get_posts( $lesson_query_args );

		// If not empty get the id otherwise move and return and empty array
		if ( ! empty( $lesson_objects ) ) {
			// Get only the lesson ids
			foreach ( $lesson_objects as $lesson ) {
				array_push( $dripping_lesson_ids, absint( $lesson->ID ) );
			}
		}

		return $dripping_lesson_ids;
	}

	/**
	 * Return all the user taking a given course
	 *
	 * @param  string $course_id
	 * @return array
	 */
	public function get_course_users( $course_id ) {
		$course_users = array();

		if ( empty( $course_id ) ) {
			return $course_users;
		}

		// Guild up the query parameters to
		// get all users in this course id
		$activity_query = array(
			'post_id' => absint( $course_id ),
			'type'    => 'sensei_course_status',
			'value'   => 'in-progress',
			'field'   => 'user_id',
		);

		$course_users =  Sensei_Utils::sensei_activity_ids( $activity_query );

		return $course_users;
	}

	/**
	 * Return a DateTime object for the given lesson ID (bwc support)
	 *
	 * @param  string $lesson_id
	 * @return DateTime|bool
	 */
	public static function date_from_datestring_or_timestamp( $lesson_id ) {
		$lesson_set_date = get_post_meta( $lesson_id, '_sensei_content_drip_details_date', true );

		if ( ! ctype_digit( $lesson_set_date ) ) {
			// backwards compatibility for data that's still using the old format
			$drip_date = new DateTime( $lesson_set_date );
		} else {
			$drip_date = DateTime::createFromFormat( 'U', $lesson_set_date );
		}

		return $drip_date;
	}
}
