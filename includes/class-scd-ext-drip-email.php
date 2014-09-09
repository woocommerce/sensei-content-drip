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
 * - 
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
		$users_lessons_email_list =  $this->get_todays_dripping_users_courses_lessons_list();

		if( !array( $users_lessons_email_list ) || empty( $users_lessons_email_list ) ){
			// do nothing today
			return; 
		}
		
		// generate the email markup and send the notifications
		//$this->send_bulk_drip_notification_emails( $users_lessons_email_list );
	} // end daily_drip_lesson_email_run

	/**
	* Return the list of users with their dripping lessons
	* 
	* @return array $users a list of users with a sub array of lessons
	*/
	public function get_todays_dripping_users_courses_lessons_list(){

		// get each user list by the different drip types
		$dynamic_dripping_users_lessons_list = $this->get_dynamic_user_courses_lessons_list();
		//$absolute_dripping_users_lessons_list = $this->get_absolute_user_courses_lessons_list();

		// both are empty return empty array and exit
		//if( empty( $dynamic_dripping_users_lessons_list ) && empty( $dynamic_dripping_users_lessons_list ) ){
		//		return  array();
		//}

		// merge the duplicates
		// for each list crate a sepererate array contain only the user id, leavin us with 2 arrays of user id's
		// merge these thow lists 
		// remove duplicates from the merged lists
		// loop through new merged list twice
			// insie this loop find al the values inside each of the original lists and assisng them to the correct ids

	}// end get_todays_dripping_users_lessons_list


	/**
	* Get_dynamic_user_courses_lessons_list() . return a list of users with their lessons 
	* dripping today
	*
	* @return array $lessons
	*/
	public function get_dynamic_user_courses_lessons_list(){
		global $woo_sensei_content_drip;

		$dynamic_users_courses_lessons =  array();

		// get all lesson id's with dripy type == 'dynamic'
		//var_dump( Sensei_Content_Drip()); die;
		$all_dynamic_lessons = $woo_sensei_content_drip->utils->get_dripping_lessons_by_type('dynamic');

		// return the emptiness
		if( empty( $all_dynamic_lessons ) ){
			// exit
			return $lessons;
		}

		// for all the dynamic lessons get their pre_requisites 
		$lessons_and_pre_requisite = $this->generate_lesson_and_pre_requisite_list( $all_dynamic_lessons );

		if(empty( $lessons_and_pre_requisite ) ){
			// exit
			return $lessons;
		}

		//get the lessons courses 
		$courses_lessons_pre_requisites = $this->get_courses_lessons( $lessons_and_pre_requisite  );

		if(empty( $courses_lessons_pre_requisites ) ){
			// exit
			return $lessons;
		}

		// create an array of users > courses > lessons
		$dynamic_users_courses_lessons = $this->get_dynamic_users_by_courses( $courses_lessons_pre_requisites );
		


		// go through each user and their lessons to see if the pre-requisite lesson was completed
		// then the lesson should be remove from the user
	/*	foreach ($dynamic_users_courses_lessons as $user_id ) {
			foreach ($user as $course => $lessons) {
				foreach ($lessons as $lesson ) {
						// if the user has not complted the previous exit
						if( !WooThemes_Sensei_Utils::user_completed_lesson( $lesson['pre_requisite_id'] , $user_id ) ){
							// remove the lesson as the user has not completed the pre-requisite
							unset( $lesson );
						}
				}	
			}		
		}	

		return $dynamic_users_courses_lessons; 
	*/	
	} // end get_dynamic_user_lessons_list


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
	public function get_courses_lessons( $lessons_and_pre_requisites ){
		// stup the return value
		$new_courses_lessons_prerequisites = array();
		
		//check incooming parameters 
		if( empty( $lessons_and_pre_requisites ) ){
			//return the defalt empty value
			return $new_courses_lessons_prerequisites;
		}

		foreach ( $lessons_and_pre_requisites as $lesson ) {
			$course_id = get_post_meta( $lesson[ 'lesson_id' ], '_lesson_course', true );

			if( !empty( $course_id ) ){
				$course  =  array( 'course_id' => $course_id, 'lessons' => array( $lesson ) );
				// push the value ontop of the return array
				$courses_lessons_prerequisites[] = $course ;
			}
		}// end for each

		// move duplicate courses so that each couse appreas only onece with possible multiple lessons
		$course_handeled = array(); // keeping track of courses
		$new_courses_lessons_prerequisites = array();

		foreach ($courses_lessons_prerequisites as $index => $course ) {

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
										'post_id' => $$course['course_id'], 
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

}// end Scd_Ext_drip_email