<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Content Drip ( scd ) Email functionality class
 *
 * This class handles all of the functionality for the plugins email functionality.
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct
 * -daily_drip_lesson_email_run
 * -get_users_lessons_dripping_today
 * -combine_users_lessons
 * -attach_users
 * -filter_lessons_dripping_today
 * -is_dripping_today
 * -send_bulk_drip_notifications
 * -send_single_email_drip_notifications
 */
class Scd_Ext_Drip_Email {
	/**
	 * Construction function that hooks into the WordPress workflow
	 */
	public function __construct() {
		$disable_email = Sensei_Content_Drip()->settings->get_setting( 'scd_disable_email_notifications' );

		if ( ! $disable_email ) {
			// Add email sending action to the cron job
			add_action ( 'woo_scd_daily_cron_hook' , array( $this, 'daily_drip_lesson_email_run' ) );
		}
	}

	/**
	 * The main email specific functionality.
	 *
	 * @return void
	 */
	public function daily_drip_lesson_email_run() {
		// Get all the users with their lessons dripping today
		$users_lessons_dripping_today = $this->get_users_lessons_dripping_today();

		if ( ! array( $users_lessons_dripping_today ) || empty( $users_lessons_dripping_today ) ) {
			return;
		}

		// Generate the email markup and send the notifications
		$this->send_bulk_drip_notifications( $users_lessons_dripping_today );
	}

	/**
	 * Return the list of users with their dripping lesson:251s
	 *
	 * @return array $users a list of users with a sub array of lessons
	 */
	public function get_users_lessons_dripping_today() {
		// Get the lesson by the type of drip content
		$all_dynamic_lessons  = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type( 'dynamic' );
		$all_absolute_lessons = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type( 'absolute' );

		// From each list get the lesson users and attache the lesson to the users
		$dynamic_users_lessons  = $this->attach_users( $all_dynamic_lessons );
		$absolute_users_lessons = $this->attach_users( $all_absolute_lessons );

		// Merge two users_lessons lists
		$all_users_lessons  = $this->combine_users_lessons( $dynamic_users_lessons , $absolute_users_lessons );

		// Remove all lessons not dripping today
		$users_lessons_dripping_today = $this->filter_lessons_dripping_today( $all_users_lessons );

		return $users_lessons_dripping_today;
	}

	/**
	 * Combine the users lessons arrays per user and return them
	 *
	 * @return array
	 */
	public function combine_users_lessons( $users_lessons_1, $users_lessons_2 ) {
		$combined = array();

		// When both are emty exit, if only one is empty continue
		if (  empty( $users_lessons_1 ) && empty( $users_lessons_2 ) ) {
			return $combined;
		}

		// Create a master loop for easier loop function

		$multi_users_lessons    = array();
		$multi_users_lessons[0] = (array) $users_lessons_1;
		$multi_users_lessons[1] = (array) $users_lessons_2;

		// Loop through each of the inputs
		foreach ( $multi_users_lessons as $users_lessons ) {
			// Skip empty inputs
			if ( ! empty( $users_lessons ) ) {
				foreach ( $users_lessons as $user_id => $lessons ) {
					if ( ! isset( $combined[ $user_id ] ) && ! empty( $lessons ) ) {
						$combined[ $user_id ] = array();
					}
					$unique_lesson_ids = array_unique( $lessons );
					foreach ( $unique_lesson_ids as $lesson_id ) {
						if ( false === array_search( $lesson_id, $combined[ $user_id ] ) ) {
							$combined[ $user_id ][] = $lesson_id;
						}

					}
				}
			}
		}

		return $combined;
	}

	/**
	 * Find all the users for the givven lessons. Note lessons without courses will be
	 * exluded. The only way to find users per course is via
	 *
	 * @param  array $lessons
	 * @return array $users_lessons
	 */
	public function attach_users( $lessons ) {
		$users_lessons = array();
		$courses_users = array();

		// Exit if not lessons are passed in
		if ( empty( $lessons ) ) {
			return array();
		}

		foreach ( $lessons as $lesson_id ) {
				// Get the lessons course
				$course_id = absint( get_post_meta( absint( $lesson_id ), '_lesson_course', true ) );

				// A lesson must have a course for the rest to work
				if ( empty( $course_id ) ) {
					continue;
				}

				$are_notifications_disabled = get_post_meta( absint( $course_id ), 'disable_notification', true );
				if ( $are_notifications_disabled ) {
					// don't send any emails if notifications are disabled for a course
					continue;
				}

				// Get all users in this course id
				$course_users = Sensei_Content_Drip()->utils->get_course_users( $course_id );

				if ( ! empty( $course_users ) ) {
					// Loop through each of the users for this course and append the lesson id to the user
					foreach ( $course_users as $user_id ) {
						$users_lessons[ $user_id ][] = $lesson_id;
					}
				}

		}

		return $users_lessons;
	}

	/**
	 * Loop through each users lessons and determine if they're dripping today.
	 *
	 * @param  array $users_lessons
	 * @return array
	 */
	function filter_lessons_dripping_today( $users_lessons ) {
		// Setup return array
		$users_dripping_lessons = array();

		foreach ( $users_lessons as $user_id => $lessons ) {
			foreach ( $lessons as $lesson_id ) {
					// If the lesson is dripping today add the details to
					if ( $this->is_dripping_today( $lesson_id , $user_id ) ) {
						$users_dripping_lessons[ $user_id ][] = $lesson_id;
					}
				}
		}

		return $users_dripping_lessons;
	}

	/**
	 * Determine if the lesson is dripping today
	 *
	 * @param  string $lesson_id
	 * @param  int $user_id
	 * @return bool
	 */
	function is_dripping_today( $lesson_id, $user_id = '' ) {
		// Setup variables needed
		$dripping_today = false;
		$today          = new DateTime( date( 'Y-m-d' ) ); // Get the date ignoring H:M:S

		// Get the lesson drip date
		$lesson_drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( absint( $lesson_id ), absint( $user_id ) );

		// If no lesson drip date could be found exit
		if ( empty( $lesson_drip_date ) ) {
			return false;
		}

		// Compare the lesson date with today
		$offset = $today->diff( $lesson_drip_date );

		/**
		 * Check if today == $lesson_drip_date
		 * the moment we pickup its not dripping avoid checking the rest
		 * of the values
		 */
		$dripping_today_flag = true;
		foreach ( $offset as $key => $value ) {
			if ( ! $dripping_today_flag ) {
				/**
				 * Do not execute anything else as this lesson
				 * has already been defined as not dripping based on
				 * a previous time interface unit ( y, m , d )
				 */
				continue;
			}

			if ( 'y' === $key || 'm' === $key || 'd' === $key ) {
				if ( 0 != $value ) {
					$dripping_today_flag = false;
				}
			}
		}

		// If the flag was not triggered to be false
		if ( $dripping_today_flag ) {
			$dripping_today = true;
		}

		return $dripping_today;
	}

	/**
	 * Go through all lesson and send and email for each user
	 *
	 * @param  array $users_lessons
	 * @return void
	 */
	public function send_bulk_drip_notifications( $users_lessons ) {
		global $sensei_email_data;

		if ( ! empty( $users_lessons ) ) {

			// Construct data array sensei needs before it can send an email
			$sensei_email_data = array(
				'template'  => 'sensei-content-drip',
				/**
				 * Content Drip email heading filter. This main email heading and shows on top of the email.
				 *
				 * @since 1.0.3
				 *
				 * @param string $email_heading
				 */
				'heading'   => apply_filters( 'scd_email_heading', __( 'New Content Available', 'sensei-content-drip' ) ),
				'user_id'   => '',
				'course_id' => '',
				'passed'    => '',
			);

			// Construct the email pieces
			$email_wrappers = array(
				'wrap_header' => Sensei()->emails->load_template( 'header' ),
				'wrap_footer' => Sensei()->emails->load_template( 'footer' ),
			);

			foreach ( $users_lessons as $user_id => $lessons ) {
				$this->send_single_email_drip_notifications( $user_id, $lessons, $email_wrappers );
			}
		}
	}

	/**
	 * Go through all lesson and send and email for each user
	 *
	 * @param  string $user_id
	 * @param  string $lessons
	 * @param  array $email_wrappers
	 * @return void
	 */
	public function send_single_email_drip_notifications( $user_id, $lessons, $email_wrappers ) {
		global $woothemes_sensei;

		if ( empty( $user_id ) || empty( $lessons ) || ! is_array( $lessons ) ) {
			return;
		}

		/**
		 * Filter content drip email subject
		 *
		 * @param string $email_subject
		 * @since 1.3.0
		 */
		$email_subject = apply_filters( 'scd_email_subject', __( 'Lessons available today', 'sensei-content-drip' ) );

		// Get the users details
		$user       = get_user_by( 'id', absint( $user_id ) );
		$first_name = $user->first_name ;
		$user_email = $user->user_email;

		if ( empty( $user_email ) ) {
			return;
		}

		// Load all the array keys from email pieces into variables
		$wrap_header = $email_wrappers['wrap_header'];
		$wrap_footer = $email_wrappers['wrap_footer'];

		/**
		 * Email user greeting filter.
		 *
		 * @since 1.0.3
		 *
		 * @param string $email_greeting Defaults to "Good Day $first_name"
		 * @param int $user_id
		 */
		$email_greeting = apply_filters( 'scd_email_greeting', __( 'Good Day', 'sensei-content-drip' ) . ' ' . $first_name );

		// Get email body text.
		$email_body = Sensei_Content_Drip()->utils->check_for_translation(
			'The following lessons will become available today:',
			'scd_email_body_notice_html'
		);

		// Get email footer text.
		$footer_text = Sensei_Content_Drip()->utils->check_for_translation(
			'Visit the online course today to start taking the lessons: [home_url]',
			'scd_email_footer_html'
		);
		$email_footer = str_ireplace(
			'[home_url]' ,
			'<a href="' . esc_url( home_url() ) . '" >' . esc_url( home_url() ) . '</a>' ,
			esc_html( $footer_text )
		);

		// Get grouped and ordered lesson data.
		$courses_and_lessons = $this->get_ordered_courses_and_lessons( $lessons );

		// Render the email template.
		ob_start();
		echo $wrap_header;
		Sensei_Content_Drip()->load_template(
			'single-email-drip-notification.php',
			array(
				'email_greeting'      => $email_greeting,
				'email_body'          => $email_body,
				'email_footer'        => $email_footer,
				'courses_and_lessons' => $courses_and_lessons,
			)
		);
		echo $wrap_footer;
		$formatted_email_html = ob_get_clean();

		// Send email.
		$woothemes_sensei->emails->send( $user_email, $email_subject, $formatted_email_html );
	}

	/**
	 * Get lesson data for each lesson grouped by course ID and ordered based on
	 * the course ordering.
	 *
	 * @since 2.0.0
	 *
	 * @param array $lessons The Lesson ID's for the lessons that are dripping today.
	 * @return array
	 */
	private function get_ordered_courses_and_lessons( $lessons ) {
		$courses_and_lessons = array();

		// Group lesson data by course ID.
		foreach ( $lessons as $lesson_id ) {
			// Get the post type object for this post id.
			$lesson    = get_post( $lesson_id );
			$course_id = absint( Sensei()->lesson->get_course_id( $lesson_id ) );

			// Setup the lesson data.
			$lesson_data          = array();
			$lesson_data['title'] = $lesson->post_title;
			$lesson_data['url']   = get_permalink( $lesson_id );

			// Add it to the list that will be ordered later.
			if ( isset( $courses_and_lessons[ $course_id ] ) && is_array( $courses_and_lessons[ $course_id ] ) ) {
				$courses_and_lessons[ $course_id ][ $lesson_id ] = $lesson_data;
			} else {
				$courses_and_lessons[ $course_id ] = array( $lesson_id => $lesson_data );
			}
		}

		// Sort list of lessons for each course.
		foreach ( $courses_and_lessons as $course_id => $lesson_data_items ) {
			// Set the current order as the default just in case the course lesson order is not set
			$ordered_lesson_data = $lesson_data_items;
			$course_lesson_order = get_post_meta( $course_id, '_lesson_order', true );

			if ( ! empty( $course_lesson_order ) ) {
				$ordered_lesson_data               = $this->order_course_lesson_items( $lesson_data_items, $course_lesson_order );
				$courses_and_lessons[ $course_id ] = $ordered_lesson_data;
			}
		}

		return $courses_and_lessons;
	}

	/**
	 * Order the lesson items according to courses and course order given.
	 * This function will remove the lesson ids from the order that do not matched the lessons array.
	 *
	 *
	 * @since 1.0.3
	 *
	 * @param array $lessons {
	 *   type string $lesson_id => $lesson_line_item
	 * }
	 *
	 * @param string $course_order csv list
	 *
	 * @return array $course_lessons {
	 *    array $course_id => $course_lessons{
	 *       $lesson_id => $lesson_line_item
	 *    }
	 * }
	 */
	public function order_course_lesson_items( $lessons = array(), $course_order ) {
		$ordered_lessons = explode( ',', $course_order );

		/**
		 * Swap keys and values so we can use the order given
		 * as the index for the values that should be returned.
		 */
		$ordered_lessons = array_flip( $ordered_lessons );
		$ordered_lessons = array_map( '__return_false', $ordered_lessons );

		foreach ( $lessons as $lesson_id => $lesson_line_item ) {
			$ordered_lessons[ $lesson_id ] = $lesson_line_item;
		}

		// Remove all false values before returning
		return array_filter( $ordered_lessons );
	}
}
