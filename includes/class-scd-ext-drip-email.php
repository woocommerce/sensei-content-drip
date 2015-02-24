<?php
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
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
	*
	*/
	public function __construct( ) {
		//add email sending action to the cron job
		add_action ( 'woo_scd_daily_cron_hook' , array( $this, 'daily_drip_lesson_email_run' ) );
	}// end __construct()

	/**
	* daily_drip_lesson_email_run. the main email specific functionality.
	*
	* @return void
	*/
	public function daily_drip_lesson_email_run(){
		// get all the users with their lessons dripping today
		$users_lessons_dripping_today =  $this->get_users_lessons_dripping_today();

		if( !array( $users_lessons_dripping_today ) || empty( $users_lessons_dripping_today ) ){
			// do nothing today
			return;
		}

		// generate the email markup and send the notifications
		$this->send_bulk_drip_notifications( $users_lessons_dripping_today );

	} // end daily_drip_lesson_email_run

	/**
	* Return the list of users with their dripping lesson:251s
	*
	* @return array $users a list of users with a sub array of lessons
	*/
	public function get_users_lessons_dripping_today() {

		// get the lesson by the type of drip content
		$all_dynamic_lessons = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type('dynamic');
		$all_absolute_lessons = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type('absolute');

		// from each list get the lesson users and attache the lesson to the users
		$dynamic_users_lessons = $this->attach_users( $all_dynamic_lessons );
		$absolute_users_lessons = $this->attach_users( $all_absolute_lessons );

		// merge two users_lessons lists
		$all_users_lessons  = $this->combine_users_lessons( $dynamic_users_lessons , $absolute_users_lessons );

		// remove all lessons not dripping today
		$users_lessons_dripping_today = $this->filter_lessons_dripping_today( $all_users_lessons );

		return $users_lessons_dripping_today;
	}// end get_users_lessons_dripping_today

	/**
	* combine the users lessons arrays per user
	*
	* @return array $users_lessons
	*/
	public function combine_users_lessons( $users_lessons_1 ,  $users_lessons_2 ) {
		$combined = array();

		// when both are emty exit, if only one is empty continue
		if(  empty( $users_lessons_1 ) && empty( $users_lessons_2 ) ) {
			return $combined;
		}
		// create a master loop for easier loop function
		$multi_users_lessons = array();
		$multi_users_lessons[0] =  $users_lessons_1;
		$multi_users_lessons[1] =  $users_lessons_2;

		// loop through each of the inputs
		foreach ( $multi_users_lessons as $users_lessons ) {
			// skip empty inputs
			if( !empty( $users_lessons ) ){
				foreach ( $users_lessons as $user_id => $lessons) {
					foreach ($lessons as $lesson_id ) {
						$combined[ $user_id ][] = $lesson_id;
					} // end foreach lessons
				}// absolute_dripping_users_lessons
			}// end if empty
		}// end for each multi_user_lessons

		return $combined;
	}// end combine_users_lessons

	/**
	* Find all the users for the givven lessons. Note lessons without courses will be
	* exluded. The only way to find users per course is via
	*
	* @param array $lessons
	* @return array $users_lessons
	*/
	public function attach_users( $lessons ) {

		$users_lessons = array();
		$courses_users = array();

		// exit if not lessons are passed in
		if( empty( $lessons ) ){
			return array();
		}

		foreach( $lessons as $lesson_id ) {
				// get the lessons course
				$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

				// a luesson must have a course for the rest to work
				if( empty( $course_id ) ){
					continue;
				}

				// get all users in this course id
				$course_users = Sensei_Content_Drip()->utils->get_course_users( $course_id );

				if( ! empty( $course_users )  ){
					// loop through each of the users for this course and append the lesson id to the user
					foreach( $course_users as $user_id ) {
						$users_lessons[$user_id][] = $lesson_id;
					}
				}

		} // for each
		return $users_lessons;

	}// end get_users_lessons.


	/**
	* loop through each users lessons and determine if they're dripping today.
	*
	* @param array $users_lessons
	* @return array $new_users_courses_lessons
	*/
	function filter_lessons_dripping_today( $users_lessons ) {

		// setup return array
		$users_dripping_lessons = array();

		foreach( $users_lessons as $user_id => $lessons  ) {
			foreach( $lessons as $lesson_id ) {
					// if the lesson is dripping today add the details to
					if( $this->is_dripping_today( $lesson_id , $user_id) ){
						$users_dripping_lessons[ $user_id ][] =  $lesson_id;
					}// end if
				}// end for each Lesson
		} // end for $users_lessons

		return $users_dripping_lessons;
	}// end filter_lessons_dripping_today

	/**
	* is_dripping_tody. determine if the lesson is dripping today
	*
	* @param string $lesson_id
	* @return bool  dripping_today
	*/
	function is_dripping_today( $lesson_id , $user_id ='' ) {

		// setup variables needed
		$dripping_today = false;
		$today = new DateTime( date('Y-m-d') ); // get the date ignoring H:M:S

		// get the lesson drip date
		$lesson_drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id , $user_id);

		// if no lesson drip date could be found exit
		if( !$lesson_drip_date ){
			return false;
		}

		// compare the lesson date with today
		$offset = $today->diff( $lesson_drip_date );

		// check if today == $lesson_drip_date
		// the moment we pickup its not dripping avoid checking the rest
		// of the values
		$dripping_today_flag = true;

		foreach ($offset as $key => $value) {

			if( !$dripping_today_flag ){
				// do not exutuing anything else as this lesson
				// has already been defined as not dripping based on
				// a previous time interfa unit ( y, m , d )
				continue;
			}

			if( 'y' == $key || 'm'  == $key  || 'd'  == $key) {
				if(  0 != $value ){
					$dripping_today_flag = false;
				}
			}
		} // end for each

		// if the flag was not triggered to be false
		if( $dripping_today_flag ){
			$dripping_today = true;
		}

		return $dripping_today;
	}// end is_dripping_tody

	/**
	* bulk_email_drip_notifications. go through all lesson and send and email for each user
	*
	* @param array $users_lessons
	* @return void
	*/
	public function send_bulk_drip_notifications( $users_lessons ) {
		global $woothemes_sensei , $sensei_email_data;

		if( ! empty( $users_lessons ) ){

			// Construct data array sensei needs before it can send an email
			$sensei_email_data = array(
				'template'			=> 'sensei-content-drip',
				'heading'			=> 'Content Drip',
				'user_id'			=> '',
				'course_id'			=> '',
				'passed'			=> '',
			);

			// construct the email pieces
			$email_wrappers['wrap_header'] = $woothemes_sensei->emails->load_template( 'header' );
			$email_wrappers['wrap_footer'] = $woothemes_sensei->emails->load_template( 'footer' );

			foreach ($users_lessons as $user_id => $lessons) {
				$this->send_single_email_drip_notifications( $user_id , $lessons , $email_wrappers );
			}// end for each $users_lessons
		}

	}// end send_bulk_drip_notifications

	/**
	* bulk_email_drip_notifications. go through all lesson and send and email for each user
	*
	* @param string $user_id
	* @param string $lessons
	* @param array $email_wrappers
	* @return void
	*/
	public function send_single_email_drip_notifications( $user_id, $lessons, $email_wrappers ) {
		global $woothemes_sensei , $sensei_email_data;

		if( empty( $user_id ) || empty( $lessons ) || ! is_array( $lessons ) ){
			return ;
		}

		//collect all information needed for sensing
		$email_subject = 'Lessons dripping today '; // todo: should be filtarable
													// todo: all things filterable

		// get the users details
		$user = get_user_by('id', $user_id );
		$first_name = $user->first_name ;
		$user_email = $user->user_email;

		if( empty( $user_email ) ){
			return ;
		}

		// load all the array keys from email pieces into variables:
		// $wrap_header
		// $wrap_footer
		extract( $email_wrappers );

		// get the settings values
		$settings['email_body_notice'] = Sensei_Content_Drip()->settings->get_setting('scd_email_body_notice_html') ;
		$settings['email_footer'] = Sensei_Content_Drip()->settings->get_setting( 'scd_email_footer_html' );

		// check for empty settings and setup the defaults
		if( empty( $settings['email_body_notice'] ) ){
			$settings['email_body_notice'] = __( 'The following lessons will become available today:' , 'sensei-content-drip' );
		}

		if( empty( $settings['email_footer'] ) ){
			$settings['email_footer'] = __(  'Visit the online course today to start taking the lessons: [home_url]' , 'sensei-content-drip' );
		}

		// setup the  the message content
		$email_heading = '<p>' . __('Good Day', 'sensei-content-drip' ) . ' ' . $first_name . '</p>';
		$email_body_notice = '<p>'. $settings['email_body_notice'] . '</p>';
		$email_body_lessons = '';
		// get the footer from the settings and replace the shortcode [home_url] with the actual site url
		$email_footer = '<p>'. str_ireplace('[home_url]'  , '<a href="'.esc_attr( home_url() ) .'" >'.esc_html( home_url() ).'</a>' , $settings['email_footer'] ) . '</p>';

		// loop through each lesson to get its title and relative url
		$email_body_lessons .= '<p><ul>';

		foreach( $lessons as $lesson_id ) {
			// get the post type object for this post id
			$lesson = get_post( $lesson_id );

			// setup the lesson line item
			$lesson_title = $lesson->post_title;
			$lesson_url = get_permalink( $lesson_id );
			$lesson_link = '<a href="' .esc_attr( $lesson_url ) . '">' . esc_html( $lesson_title ) . '</a>';
			$lesson_line_item = '<li>'. $lesson_link .'</li>';

			// append the li line item to the email body lessons
			$email_body_lessons .= $lesson_line_item;
		}// end for each $lessons
		$email_body_lessons .= '</ul></p>';

		// assemble the message content
		$formated_email_html = $wrap_header . $email_heading . $email_body_notice . $email_body_lessons . $email_footer .  $wrap_footer ;

		// send
		$woothemes_sensei->emails->send( $user_email, $email_subject, $formated_email_html );

		return;
	}// end bulk_email_drip_notifications
}// end Scd_Ext_drip_email