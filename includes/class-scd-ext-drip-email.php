<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
/*
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
 * - __construct()
 * - daily_drip_lesson_email_run()
 * - get_todays_dripping_users_lessons_list()
 * - get_dynamic_user_courses_lessons_list
 * - generate_lesson_and_pre_requisite_list()
 * - get_courses_lessons()
 * - get_dynamic_users_by_courses
 */

class Scd_Ext_drip_email {

	/**
	* Construction function that hooks into the WordPress workflow
	*/
	public function __construct( ){

		//add email sending acction to the cron job
		add_action ( 'woo_scd_daily_cron_hook' , array( $this, 'daily_drip_lesson_email_run' ) );
	}// end __construct()

	/**
	* daily_drip_lesson_email_run. the main email specific functionality.
	*
	* @return void
	*/
	public function daily_drip_lesson_email_run(){
	
	
		// get all the users with their lessons
		$users_lessons_dripping_today =  $this->get_users_lessons_dripping_today();

		if( !array( $users_lessons_dripping_today ) || empty( $users_lessons_dripping_today ) ){
			// do nothing today
			return; 
		}
		
		// generate the email markup and send the notifications
		$this->send_bulk_drip_notifications( $users_lessons_dripping_today );
	
	} // end daily_drip_lesson_email_run

	/**
	* Return the list of users with their dripping lessons
	* 
	* @return array $users a list of users with a sub array of lessons
	*/
	public function get_users_lessons_dripping_today(){

		// get each user list by the different drip types
		$dynamic_dripping_users_lessons = $this->get_dynamic_user_courses_lessons_dripping_today();
		$absolute_dripping_users_lessons = $this->get_absolute_user_courses_lessons_dripping_today();

		// merge the duplicates
		$final_users_lesson = array();

		// add the dynamic_dripping_users_lessons to the final users and lessons array
		if( !empty( $dynamic_dripping_users_lessons ) ){
			foreach ($dynamic_dripping_users_lessons as $user ) {
				$user_id  = $user['user_id'];
				$courses = $user['courses'];

				foreach( $courses as $course) {
					foreach ( $course['lessons'] as $lesson_id) {
						// pop the lesson id on top of the curret user key
						$final_users_lesson[ $user_id ][] = $lesson_id;
					
					}// end foreach $course['lessons']
				}// end foreach courses
			} // end foreach  dynamic_dripping_users_lessons
		}

		// add the  absolute_dripping_users_lessons to the final users and lessons array
		if( !empty( $absolute_dripping_users_lessons ) ){
			foreach ( $absolute_dripping_users_lessons as $user_id => $lessons) {
				foreach ($lessons as $lesson_id ) {
					$final_users_lesson[ $user_id ][] = $lesson_id;
				} // end foreach lessons
			}// absolute_dripping_users_lessons
		}

		return $final_users_lesson;
	}// end get_users_lessons_dripping_today


	/**
	* Get_dynamic_user_courses_lessons_list() . return a list of users with their lessons 
	* dripping today
	*
	* @return array $dynamic_users_lessons_courses_dripping_today
	*/
	public function get_dynamic_user_courses_lessons_dripping_today(){
		global $woo_sensei_content_drip;

		$dynamic_users_lessons_courses_dripping_today =  array();

		// get all lesson id's with dripy type == 'dynamic'
		$all_dynamic_lessons = $woo_sensei_content_drip->utils->get_dripping_lessons_by_type('dynamic');

		// return the emptiness
		if( empty( $all_dynamic_lessons ) ){
			// exit
			return $dynamic_users_lessons_courses_dripping_today;
		}

		// for all the dynamic lessons get their pre_requisites 
		$lessons_and_pre_requisite = $this->generate_lesson_and_pre_requisite_list( $all_dynamic_lessons );

		if(empty( $lessons_and_pre_requisite ) ){
			// exit
			return $dynamic_users_lessons_courses_dripping_today;
		}

		//get the lessons courses 
		$courses_lessons_pre_requisites = $this->get_courses_lessons( $lessons_and_pre_requisite  );

		// create an array of users > courses > lessons
		$dynamic_users_courses_lessons = $this->get_dynamic_users_by_courses( $courses_lessons_pre_requisites );

		//check all course to see if they are drippping today
		$dynamic_users_lessons_courses_dripping_today = $this->get_dripping_today( $dynamic_users_courses_lessons );

		return $dynamic_users_lessons_courses_dripping_today; 	
	} // end get_dynamic_user_courses_lessons_dripping_today

	/**
	* get_absolute_user_courses_lessons_dripping_today returns a list of user with their lessons dripping today
	*
	* @param array $lessons_and_pre_requisites list of lesson id and their pre requistes
	* @return array $lessons_and_pre_requisites
	*/
	public function get_absolute_user_courses_lessons_dripping_today(){
		global $woo_sensei_content_drip;
		
		$aboslute_users_lessons_dripping_today = array();

		// get all lesson with the abosulte type
		$all_abslute_lessons = $woo_sensei_content_drip->utils->get_dripping_lessons_by_type('absolute');
		// exit early  if empty 
		if(  empty( $all_abslute_lessons ) ){
			return array();
		}

		// determine if the lessons are dripping today
		$absolute_dripping_lessons = array();

		foreach ($all_abslute_lessons as $lesson_id) {
			// check the current lesson
			if( $this->is_dripping_today( $lesson_id )  ){
					$absolute_dripping_lessons[] = $lesson_id;
			}
		} // end for each all_abslute_lessons

		// if nothing drips send the empty result back to the requester
		if(  empty( $absolute_dripping_lessons ) ){
			return array();
		}
		// get teh list of users and the lesson dripping to day
		$aboslute_users_lessons_dripping_today = $this->attach_users( $absolute_dripping_lessons );

		return $aboslute_users_lessons_dripping_today;

	}// end get_absolute_user_courses_lessons_dripping_today

	/**
	* generate_lesson_and_pre_requisite_list return a list lessons with of their pre-requisite
	*
	* @param array $lessons_and_pre_requisites list of lesson id and their pre requistes
	* @return array $lessons_and_pre_requisites
	*/
	public function generate_lesson_and_pre_requisite_list( $lessons ){

		// setup the return variable
		$lessons_and_pre_requisites = array();

		if( empty( $lessons ) || ! is_array( $lessons ) ){
			return $lessons_and_pre_requisites;
		}

		// go through each lesson find the pre-requisite
		foreach ($lessons as $lesson_id ) {
			$pre_requisite_id = get_post_meta(  $lesson_id , '_sensei_content_drip_dynamic_pre_lesson_id' , true );
			$lessons_and_pre_requisites[] =  array('lesson_id' => $lesson_id, 'pre_requisite'=> $pre_requisite_id );
		}

		return $lessons_and_pre_requisites;

	}// end generate_lesson_and_pre_requisite_list

	/**
	*  Retrieves all courses for the given lessons array
	*  
	* this function loops through the all lessons passend in getting their courses
	*
	* @param array $courses_lessons :  array( $course_id =>  array( $numeric_key => array( 'lesson_id' => $lesson_id , 'pre_requisite_id' => $pre_requisite  )
	*/	
	public function get_courses_lessons( $lessons ){
		// stup the return value
		$new_courses_lessons_prerequisites = array();
		
		//check incooming parameters 
		if( empty( $lessons ) ){
			//return the defalt empty value
			return $new_courses_lessons_prerequisites;
		}

		// assign all lesson to there courses 
		$courses_lessons = array();
		foreach ( $lessons as $lesson ) {
			$course_id = get_post_meta( $lesson[ 'lesson_id' ], '_lesson_course', true );

			if( !empty( $course_id ) ){
				$course  =  array( 'course_id' => $course_id, 'lessons' => array( $lesson ) );
				// push the value ontop of the return array
				$courses_lessons[] = $course ;
			}
		}// end for each

		// move duplicate courses so that each couse appreas only onece with possible multiple lessons
		$course_handeled = array(); // keeping track of courses
		$new_courses_lessons_prerequisites = array();

		foreach( $courses_lessons as $index => $course ) {

			// setup the current values for easier reference
			$current_course_id = $course[ 'course_id' ];

			// skip if this course was handled already
			if( in_array( $current_course_id,  $course_handeled) ){
				continue; 
			}

			// move from the current course forward throught the array
			$next_item_index =  $index + 1 ;

			for( $j = $next_item_index ; $j < count( $courses_lessons_prerequisites ) ; $j++ ){ 

				// get current course the course id
				$for_loop_course = $courses_lessons_prerequisites[$j];

				// if this id is equal to the upper loops id add the current courses lessons to the
				// upper course and delte the current course
				if( $for_loop_course['course_id'] === $current_course_id  ){
					// assign each lesson to the curent upper for each scope course 
					foreach ( $for_loop_course['lessons']  as $lesson) {
					 	$course['lessons'][] = $lesson;
					} 
				}
			} // end for loop 

			// push th current course onto the new array
			$new_courses_lessons_prerequisites[] = $course;

			// keep track of the courses that were handled to avoid duplication
			$course_handeled[] = $current_course_id;

		}// end for each

		return $new_courses_lessons_prerequisites;
	}	// end get_courses_lessons

	/**
	* Find all the users for the givven lessons. Note lessons without courses will be 
	* exluded. The only way to find users per course is via 
	*
	* @param array $lessons 
	* @return array $users_lessons
	*/
	public function attach_users( $lessons ){
		
		$users_lessons = array();
		$courses_users = array();

		// exit if not lessons are passed in
		if( empty( $lessons ) ){
			return array();
		}

		foreach ($lessons as $lesson_id) {
				
				// get the lessons course
				$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

				if( empty( $course_id ) ){
					continue;
				}

				// if the key exist we already have the users for this course, hence no need to fetch theme again
				if ( ! array_key_exists( $course_id, $courses_users ) ) {
					// build up the query parameters
					$activitiy_query = array( 
											'post_id' => $course_id, 
											'type' => 'sensei_course_start', 
											'field' => 'user_id' 
										);
					$course_users[ $course_id ] =  WooThemes_Sensei_Utils::sensei_activity_ids( $activitiy_query );
				}

				if( ! empty( $course_users[ $course_id ] )  ){
					// loop through each of the users for this course and append the lesson id to the user
					foreach( $course_users[ $course_id ] as $user_id ) {
						$users_lessons[$user_id][] = $lesson_id; 
					}
				}

		} // for each
		return $users_lessons;

	}// end get_users_lessons. 

	/**
	* Find all the users who are subscribing to the array of courses
	*
	* @param array $courses
	* @return array $users_courses
	*/
	public function get_dynamic_users_by_courses( $courses_lessons ){
		
		global $woothemes_sensei; 

		$new_users_courses_lessons = array();
		$users_courses_lessons = array();

		if( empty( $courses_lessons ) || !is_array( $courses_lessons ) ){
			// default users set to empty array
			return $new_users_courses_lessons;
		}	

		// get the users for each course
		foreach ( $courses_lessons as $course ) {

			if( 'course' == get_post_type( $course['course_id'] )  ){
						
				// get the previous lessons completion date
				$activitiy_query = array( 
										'post_id' => $course['course_id'], 
										'type' => 'sensei_course_start', 
										'field' => 'user_id' 
									);

				$course_users =  WooThemes_Sensei_Utils::sensei_activity_ids( $activitiy_query );
				
				// check if there are users
				if( !empty( $course_users ) && is_array( $course_users ) ){

					// combine users and courses
					foreach ($course_users as $user ) {
						$user_data = array(
								'user_id'=> $user,
								'courses'=> array( $course ) , // array as there may be more courses for this uers
						);
						// push the user on top of th list of users
						$users_courses_lessons[] = $user_data;

					} // end foreach course_users as user
				
				} // end if course_users is valid array
			
			}// end for each $courses_lessons as $course 
		
		} // end if post type == course

		
		// merge duplicate users
		$users_handeled = array(); // keeping track of courses

		foreach ( $users_courses_lessons as $user ) {

			// setup the current values for easier reference
			$current_user_id = $user[ 'user_id' ];

			// skip if this user was handled already
			if( in_array( $current_user_id,  $users_handeled) ){
				continue;
			}

			// move from the current course forward throught the array
			$next_item_index =  $index + 1 ;

			for( $j = $next_item_index ; $j < count( $users_courses_lessons ) ; $j++ ){ 

				// get current course the course id
				$for_loop_user = $users_courses_lessons[ $j ];

				// if this id is equal to the upper loops id add the current courses lessons to the
				// upper course and delte the current course
				if( $for_loop_user['user_id'] === $current_user_id  ){
					// assign each lesson to the curent upper for each scope course 
					foreach ( $for_loop_user['courses'] as $course) {
					 $user['courses'][] = $course;
					} 
				}
			} // end for loop 

			// push th current course onto the new array
			$new_users_courses_lessons[] = $user;

			// keep track of the courses that were handled to avoid duplication
			$users_handeled[] = $current_user_id;

		}// end for each

		return $new_users_courses_lessons;
	}// end get_users_by_courses

	/**
	* loop through each users lessons and determine if they're dripping today.
	*
	* @param array users_courses_lessons
	* @return array $new_users_courses_lessons 
	*/
	function get_dripping_today( $users_courses_lessons ){

		$new_users_courses_lessons = array();

		foreach( $users_courses_lessons as $user ) {			
			// setup an new user to be used if the lesson is dripping
			// the purpost for doing it this way is to avoice user and course duplication 
			// in the return array
			$new_user = array();
			$new_user['user_id'] = $user['user_id'];

			foreach( $user['courses'] as $course ) {
				// setup the course to be used in ther are lessons dripping today
				$new_course = array();
				$new_course['course_id'] = $course['course_id'];

				foreach ($course['lessons'] as $lesson ) {
					// if the lesson is dripping today add the details to 
					if( $this->is_dripping_today( $lesson['lesson_id'] , $user['user_id'] ) ){
						$new_course['lessons'][] =  $lesson;
					}// end if

				}// end for each Lesson
				
				// if the inner loop has lessons dripping add this new course to the new user
				if( !empty( $new_course['lessons'] ) ){
					$new_user['courses'][] = $new_course;
				}	

			} // end for each Course

			// if the user has courses with lesson created in the inner 
			// loops add this user to the returning array
			if( !empty( $new_user['courses']  ) ){
				$new_users_courses_lessons[] = $new_user;
			}

		} // end for each User
	    
		return $new_users_courses_lessons;
	}// end get_dripping_today

	/**
	* is_dripping_tody. determine if the lesson is dripping today
	*
	* @param string $lesson_id 
	* @return bool  dripping_today
	*/
	function is_dripping_today( $lesson_id , $user_id ='' ){

		// setup variables needed 
		$dripping_today = false;
		$today = new DateTime( date('Y-m-d') ); // get the date ignoring H:M:S
		
		// get the lesson drip date
		$lesson_drip_date = $this->get_lesson_drip_date( $lesson_id , $user_id);
		
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

			if( 'y' == $key || 'm'  == $key  || 'd'  == $key){
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
	* get_lesson_drip_date. determine the drip type and return the date teh lesson will become available
	*
	* @param string $lesson_id 
	* @param string $user_id
	* @return DateTime  drip_date format yyyy-mm-dd
	*/
	public function get_lesson_drip_date( $lesson_id , $user_id ){
		//setup the basics, drip date default return will be false on error
		$drip_date = false;

		if( empty( $lesson_id ) ){
			return $drip_date; // exit early 
		}

		//get the post meta drip type
		$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true );

		// we need a user id if the drip type is dynamice
		if( 'dynamic' === $drip_type  && empty( $user_id )  ){
			return $drip_date; // exit early
		}

		if( 'absolute' === $drip_type ){
			$lesson_set_date = get_post_meta( $lesson_id ,'_sensei_content_drip_details_date', true  );

			if ( empty( $lesson_set_date ) ) {
				return $drip_date; // exit early  
			}

			$drip_date = new DateTime( $lesson_set_date );

		}elseif( 'dynamic' === $drip_type  ){
			// get the lessons data
			$dripped_data =  Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );

			// get the drip details array data
			$unit_type  =  get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_type', true );  
			$unit_amount = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_amount', true );
			$drip_pre_lesson_id = get_post_meta( $lesson_id , '_sensei_content_drip_dynamic_pre_lesson_id', true ); 
			
			// get the previous lessons completion date
			$activitiy_query = array( 'post_id' => $drip_pre_lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date_gmt' );
			$user_lesson_end_date_gmt =  WooThemes_Sensei_Utils::sensei_get_activity_value( $activitiy_query  );

			// check if the user has finished the previous course
			if( !$user_lesson_end_date_gmt  ){
				return false;
			}

			// create a date interval object to determine when the lesson should become available
			$unit_type_first_letter_uppercase = strtoupper( substr( $unit_type, 0, 1 ) ) ; 
			$interval_to_lesson_availablilty = new DateInterval( 'P'.$unit_amount.$unit_type_first_letter_uppercase );

			// create an object which the interval will be added to and add the interval
			$lesson_becomes_available_date = new DateTime( $user_lesson_end_date_gmt );

			$drip_date = $lesson_becomes_available_date->add( $interval_to_lesson_availablilty );

		}// end if

		//strip out the hours minutes and seccond before returning the yyyy-mm-dd format
		return  new DateTime( $drip_date->format('Y-m-d') );
	}// end get_lesson_drip_date()

	/**
	* bulk_email_drip_notifications. go through all lesson and send and email for each user
	*
	* @param array $users_lessons
	* @return void
	*/
	public function send_bulk_drip_notifications( $users_lessons ){

		if( ! empty( $users_lessons ) ){
			foreach ($$users_lessons as $user_id => $lessons) {
					
				$this->send_single_email_drip_notifications( $user_id , $lessons );

			}// end for each $users_lessons
		}

	}// end send_bulk_drip_notifications

	/**
	* bulk_email_drip_notifications. go through all lesson and send and email for each user
	*
	* @param string $user_id
	* @param string $lessons
	* @return void
	*/
	public function send_single_email_drip_notifications( $user_id, $lessons ){
		global $woothemes_sensei;

		if( empty( $user_id ) || empty( $lessons )  ){
			return ;
		}

		// get the users details
		$user = get_user_by('id', $user_id );
		$display_name =  
		$user_email = 

		// setup the  the message content
		$email_heading = __('Good Day', 'sensei-content-drip' ). ' ' . $display_name . '<br> <br>';
		$email_body_notice = 'The following lessons will become abailable today: <br> ';
		$email_body_lessons = '';
		$meail_footer = ' Visit the online course today to srart taking the lessons: '. home_url() ; //get this for the settings

		// loop through each lesson to get its title and relative url
		$email_body_lessons .= '<ul>';
		foreach( $lessons as $lesson_id ) {
			// get the post type object for this post id
			$lesson = get_post( $lesson_id );

			// setup the lesson line item 
			$lesson_title = $lesson->post_title;
			$lesson_url = get_post_permalink( $lesson_id );

			$lesson_link = '<a href="' . $lesson_url . '">' . $lesson_title . '</a>';
			$lesson_line_item = '<li>'. $lesson_link .'</li>';

			// append the li line item to the email body lessons
			$email_body_lessons .= $lesson_line_item ;
		}// end for each $lessons
		$email_body_lessons .= '</ul>';

		// assemble the message content
		$email_html = $email_heading . $email_body_notice . $email_body_lessons . $meail_footer;

		// sensei calls to format message nicely
		$sensei_formated_email_html = $woothemes_sensei->email->wrap_message( $email_html );

		//collect all information needed for sensing
		$email_subject = 'Lessons dripping today '; // should be ins ettings 

		$woothemes_sensei->email->send( $user_email, $email_subject, $sensei_formated_email_html );

	}// end bulk_email_drip_notifications

	

}// end Scd_Ext_drip_email