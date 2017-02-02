<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Content Drip ( scd ) Extension Access Control class
 *
 * The class controls all frontend activity relating to sensei lessons.
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 * - _token
 * - drip_message
 * - __construct
 * - is_lesson_access_blocked
 * - is_absolute_drip_type_content_blocked
 * - is_dynamic_drip_type_content_blocked
 * - get_lesson_drip_date
 */

class Scd_Ext_Access_Control {

	/**
	 * The token.
	 * @var    string
	 * @access private
	 * @since  1.0.0
	 */
	private $_token;

	/**
	 * The message shown in place of lesson content
	 * @var    string
	 * @access protected
	 * @since  1.0.0
	 */
	protected $drip_message;

	/**
	 * constructor function
	 */
	public function __construct() {
		// set a formatted  message shown to user when the content has not yet dripped
		$this->message_format = Sensei_Content_Drip()->utils->check_for_translation(
			'This lesson will become available on [date].',
			'scd_drip_message'
		);
	}// end __construct()

	/**
	 * Check if  the lesson can be made available to the the user at this point
	 * according to the drip meta data
	 *
	 * @since  1.0.0
	 * @param  int $lesson_id
	 * @return bool $content_access_blocked
	 */
	public function is_lesson_access_blocked( $lesson_id ) {
		$content_access_blocked = false;
		$lesson_course_id       = Sensei()->lesson->get_course_id( $lesson_id );

		// Return drip not active for the following conditions.
		if ( is_super_admin() || empty( $lesson_id ) || 'lesson' !== get_post_type( $lesson_id )
		     || Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() )
		     || ! Sensei_Utils::user_started_course( $lesson_course_id, get_current_user_id() ) ) {
			return false;
		}

		// get the lessons drip data if any
		$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true );

		// check if the content should be dripped
		if ( empty( $drip_type ) || 'none' === $drip_type ) {
			$content_access_blocked = false;
		} elseif ( 'absolute' === $drip_type  ) {
			$content_access_blocked = $this->is_absolute_drip_type_content_blocked( $lesson_id  );
		} elseif ( 'dynamic' === $drip_type ) {
			$content_access_blocked = $this->is_dynamic_drip_type_content_blocked( $lesson_id  );
		}

		/**
		 * filter scd_is_drip_active
		 * filter scd_lesson_content_access_blocked
		 *
		 * @param boolean $content_access_blocked
		 *
		 * Filter the boolean value returned. The value tells us if a drip is active on the given lesson
		 */
		$content_access_blocked = apply_filters( 'scd_is_drip_active' , $content_access_blocked , $lesson_id ); // backward compatible
		$content_access_blocked = apply_filters( 'scd_lesson_content_access_blocked' , $content_access_blocked , $lesson_id );

		return $content_access_blocked;
	}

	/**
	 * Check specifically if the absolute drip type is active on this lesson
	 * depending only on the date stored on this lesson
	 *
	 * @since  1.0.0
	 * @param  array $lesson_id
	 * @return bool $active
	 */
	public function is_absolute_drip_type_content_blocked( $lesson_id ) {
		// Setup the default drip status
		$access_blocked = false;

		// Get the user details
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		// Convert string dates to date ojbect
		$lesson_drip_date = $this->get_lesson_drip_date( $lesson_id , $user_id );
		$today            = new DateTime();

		/**
		 * Compare dates
		 *
		 * If lesson drip date is greater than the today
		 * the drip date ist still active and lesson content should be hidden
		 */
		if ( $lesson_drip_date > $today ) {
			$access_blocked  = true;
		}

		return $access_blocked;
	}

	/**
	 * Check specifically if the dynamic drip content is active on this lesson
	 * depending only on the time span specified by the user
	 *
	 * @since  1.0.0
	 * @param  string $lesson_id
	 * @return bool $active
	 */
	public function is_dynamic_drip_type_content_blocked( $lesson_id ) {
		// Setup the default drip status
		$access_blocked = false;

		// Get the lessons data
		$dripped_data = Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );

		// Confirm that all needed data is in place otherwise this content will be available
		if ( empty( $dripped_data )
			|| empty( $dripped_data['_sensei_content_drip_details_date_unit_type'] )
			|| empty( $dripped_data['_sensei_content_drip_details_date_unit_amount'] ) ) {
			// default set to false
			return $access_blocked;
		}

		// If the user is not logged in ignore this type and exit
		if ( ! is_user_logged_in() ) {
			return $access_blocked;
		}

		// Get the user details
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		// Get the drip details array data
		$unit_type   = $dripped_data['_sensei_content_drip_details_date_unit_type'];
		$unit_amount = $dripped_data['_sensei_content_drip_details_date_unit_amount'];

		// If the data is not correct then the drip lesson should be shown
		if ( ! in_array( $unit_type, array( 'day','week' ,'month' ) ) || ! is_numeric( $unit_amount ) ) {
			return $access_blocked;
		}

		$lesson_becomes_available_date = $this->get_lesson_drip_date( $lesson_id , $user_id );
		$today                         = new DateTime(); // Get today's date

		/**
		 * Compare dates
		 *
		 * If lesson_becomes_available_date is greater than the today
		 * the drip date ist still active and lesson content should be hidden
		 */
		if ( $lesson_becomes_available_date > $today ) {
			$access_blocked  = true;
		}

		return $access_blocked;
	}

	/**
	 * Determine the drip type and return the date the lesson will become available
	 *
	 * @param  string $lesson_id
	 * @param  string $user_id
	 * @return DateTime drip_date format yyyy-mm-dd
	 */
	public function get_lesson_drip_date( $lesson_id , $user_id = '' ) {
		// Setup the basics, drip date default return will be false on error
		$drip_date = false;

		if ( empty( $lesson_id ) ) {
			return $drip_date;
		}

		// Get the post meta drip type
		$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true );

		// We need a user id if the drip type is dynamic
		if ( 'dynamic' === $drip_type  && empty( $user_id ) ) {
			return false;
		}

		if ( 'absolute' === $drip_type ) {
			$drip_date = Scd_Ext_Utils::date_from_datestring_or_timestamp( $lesson_id );
		} elseif ( 'dynamic' === $drip_type ) {
			// Get the drip details array data
			$unit_type   = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_type', true );
			$unit_amount = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_amount', true );

			// Get the lesson course
			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

			// The lesson must belong to a course for this drip type to be active
			if ( empty( $course_id ) ) {
				return false;
			}

			// Get the previous lessons completion date
			$activity_query_args = array(
				'post_id' => $course_id,
				'user_id' => $user_id,
				'type'    => 'sensei_course_status'
			);

			// Get the activity/comment data
			$activity = Sensei_Utils::user_course_status( $course_id , $user_id );

			if ( isset( $activity->comment_ID ) && intval( $activity->comment_ID ) > 0 ) {
				$course_start_date = get_comment_meta( $activity->comment_ID , 'start' , true );
			}

			// Make sure there is a start date attached the users sensei_course_status comment data on the course
			if ( ! empty( $course_start_date ) ) {
				$user_course_start_date_string = $course_start_date;
			} else if ( isset( $activity->comment_date_gmt ) && ! empty( $activity->comment_date_gmt ) ) {
				// This is for backwards compatibility for users who have not yet
				// updated to the new course status data format since sensei version 1.7.0
				$user_course_start_date_string = $activity->comment_date_gmt;
			} else {
				return false;
			}

			// Create an object which the interval will be added to and add the interval
			$user_course_start_date = new DateTime( $user_course_start_date_string );

			// Create a date interval object to determine when the lesson should become available
			$unit_type_first_letter_uppercase = strtoupper( substr( $unit_type, 0, 1 ) ) ;
			$interval_to_lesson_availability  = new DateInterval( 'P' . $unit_amount . $unit_type_first_letter_uppercase );

			// Add the interval to the start date to get the date this lesson should become available
			$drip_date = $user_course_start_date->add( $interval_to_lesson_availability );
		}

		// Strip out the hours minutes and second before returning the yyyy-mm-dd format
		return new DateTime( $drip_date->format( 'Y-m-d' ) );
	}
}
