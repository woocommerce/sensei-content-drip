<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Content Drip Utilities Class
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
 *
 *
 */
class Sensei_Scd_Extension_Utils {
	/**
	*   Returns all the lesson with passed in drip type 
	*	
	*	@return array $lessons array containing lesson ids 
	*   @return array empty for no matching values
	*/
	public function get_dripping_lessons_by_type( $type ){

		//setup the return value
		 $dripping_lesson_ids = array();

		if( empty( $type ) ){
			return $dripping_lesson_ids;
		}

		// if type none return all lessons with no meta query
		if( 'none' == $type ){
			$meta_query = '';
		}else{
			$meta_query = array( array(
									'key' => '_sensei_content_drip_type',
									'limit' => 200,
									'value' => $type, 
									),);
		}

		// create the lesson query args
		$lesson_query_args = array( 
							'post_type' => 'lesson' , 
							'meta_query'=>  $meta_query,
							);	 

		// fetch all posts matching the arguments
		$lesson_objects = get_posts( $lesson_query_args );

		// if not empty get the id otherwise move and return and empty array
		if( !empty($lesson_objects) ){
			//get only the lesson ids
			foreach ( $lesson_objects as $lesson ) {
				array_push( $dripping_lesson_ids , $lesson->ID );
			}
		}

		return $dripping_lesson_ids;
	}// end get_dripping_lessons_by_type

	/**
	* get_lesson_drip_date. determine the drip type and return the date teh lesson will become available
	*
	* @param string $lesson_id 
	* @param string $user_id
	* @return DateTime  drip_date format yyyy-mm-dd
	*/
	public function get_lesson_drip_date( $lesson_id , $user_id ){
		global $woo_sensei_content_drip;
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
	*   This function checks the lesson drip type
	*
	*
	*	@param  string | int $lesson_id
	*	@return string $drip_type ( 'none' || 'absolute' || 'dynamic' )
	*/
	public function get_lesson_drip_type( $lesson_id ){

		// basics, checking out the passed in lesson object
		if( empty( $lesson_id) || 'lesson' != get_post_type( $lesson_id ) ){
			return 'none';
		}

		// retrive the drip type from the lesson
		$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true ) ;

		// send back the type string
		return  empty( $drip_type ) ? 'none' : $drip_type; 

	} // end get_drip_type


} // end class Sensei_Scd_Extension_Utils