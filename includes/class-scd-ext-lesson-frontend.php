<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Sensei Content Drip ( scd ) Exctension lesson frontend class
 *
 * Thie class controls all frontend activitiy relating to sensei lessons.
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
 * - hide_lesson_content( $lesson_id , $new_content)
 * - is_lesson_dripped( $lesson )
 */

class Scd_ext_lesson_frontend {

/**
 * The token.
 * @var     string
 * @access  private
 * @since   1.0.0
 */
private $_token;

/**
 * The message shown in place of lesson content
 * @var     string
 * @access  protected
 * @since   1.0.0
 */
protected $drip_message;


/**
* constructor function
*
* @uses add_filter
*/
public function __construct(){
	// set a formated string
	$this->absolute_formatted_message = "This lesson will become available on: [date]"; 
	$this->dynamic_formatted_message = 'This lesson content will only become available [unit-amount] [unit-type] '
										.'after you complete the previous lesson';

	// set a formated string
	$this->title_append_text = ": Not Available"; 

	// hook int all post of type lesson to determin if they are 
	add_filter('the_posts', array( $this, 'lessons_drip_filter' ), 1 );

}// end __construct()


/**
* single_course_lessons_content, loops through each post on the single crouse page 
* to confirm if ths content should be hidden
* 
* @since 1.0.0
* @param array $posts
* @return array $posts
* @uses the_posts()
*/

public function lessons_drip_filter( $lessons ){
	// this should only apply to the front end on single course and lesson pages
	if( is_admin() ||  empty( $lessons ) ){
		return $lessons;	
	}
	

	//the first post in the array should be of post type lesson
	if( 'lesson' !== $lessons[0]->post_type  ){
		return $lessons;
	}
	 
	// loop through each post and replace the content
	foreach ($lessons as $lesson) {
		if ( $this->is_lesson_drip_active( $lesson ) ){
			// change the lesson content accordingly
			$lesson =  $this->make_lesson_unavailable( $lesson, $this->absolute_formatted_message  );
		}
	}

	return $lessons;
} // end lessons_drip_filter


/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function is_lesson_drip_active( $lesson ){

	$dripped = false;

	//var_dump($lesson);
	// return drip not active for the fllowing conditions
	if( is_super_admin() || empty($lesson) || 'lesson' !== $lesson->post_type ){
		return $dripped;
	}

	// get the lessons drip data if any 
	$dripped_data = get_post_meta( $lesson->ID , '_sensei_drip_content', true );

	// check if the content should be dripped
	if( empty( $dripped_data ) || !isset( $dripped_data['drip_type'] ) || 'none' === $dripped_data['drip_type'] ) {
		$dripped = false;
	}elseif( 'absolute' === $dripped_data['drip_type']  ){
		$dripped = $this->is_absolute_drip_active( $dripped_data ); 
	}elseif( 'dynamic' === $dripped_data['drip_type']  ){
		$dripped = $this->is_dynamic_drip_active( $dripped_data , $lesson->ID  );
	}

	// check the post data and alter $dripped
	return $dripped;
} // end is_lesson_dripped



/**
* Check specifically if the absolute drip is active on this lesson
* depending only on the date
* 
* @since 1.0.0
* @param  array $dripped_data
* @return bool $active
*/

public function is_absolute_drip_active( $dripped_data ){
	// setup the default drip status 
	$drip_status = false;

	// confirm that all needed data is in place otherwise return false
	if( empty( $dripped_data ) || !isset( $dripped_data['drip_type'] ) 
		|| !isset( $dripped_data['drip_details'] ) || 'absolute' !== $dripped_data['drip_type'] ) {
		return $drip_status;
	}

	// convert string dates to date ojbect
	$lesson_drip_date = new DateTime( $dripped_data['drip_details'] );
	$today = new DateTime();

	// compare dates
	// if lesson drip date is greater than the today the drip date ist still active and lesson content should be hidden
	if( $lesson_drip_date  > $today  ){
		$drip_status  = true;
	}

	// finaly return $drip_status
	return $drip_status;

} //  end is_absolute_drip_active

/**
* Check specifically if the dynamic drip content is active on this lesson
* depending only on the time span specified by the user
* 
* @since 1.0.0
* @param array $dripped_data
* @param string $lesson_id
* @return bool $active
*/
public function is_dynamic_drip_active( $dripped_data , $lesson_id ){
	global $woothemes_sensei;

	// setup the default drip status 
	$drip_status = false;

	// confirm that all needed data is in place otherwise return false
	if( empty( $dripped_data ) || !isset( $dripped_data['drip_type'] ) 
		|| !isset( $dripped_data['drip_details'] ) || 'dynamic' !== $dripped_data['drip_type'] ) {
		return $drip_status;
	}

	// if the user is not logged in ignore this type and show the blocked 
	// lesson content  as sensei normally would
	if( !is_user_logged_in() ){
		return $drip_status;
	}

	// get the user details
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	// get the drip details array data
	$details = $dripped_data['drip_details'];
	$unit_type  =  $details['unit-type'];
	$unit_amount = $details['unit-amount'];

	// if the data is not correct there an error and this drip is not active
	if( !in_array($unit_type, array( 'day','week' ,'month' ) ) || ! is_numeric( $unit_amount )  ){
		// trigger an error for the user to understand what just went wrong so they can tell support what happend
		if ( WP_DEBUG ){
			trigger_error( __( 'Sensei Content Drip > dynamic drip data for this lesson was not setup correctly' , 'sensei-content-dript' ));
		}
		return $drip_status;
	}

	// get previous lesson completeion date 
	$prerequisite_lesson_id = get_post_meta( $lesson_id, '_lesson_prerequisite', true );

	// if pre-requisite lesson does not exist get the course start date
	if( empty( $prerequisite_lesson_id )  ){
		// this is not dripped if the pre - requisite lesson is emtpy
		return $drip_status;
	}

	// if the user has not complted the previous exit
	if( !WooThemes_Sensei_Utils::user_completed_lesson( $prerequisite_lesson_id , $user_id ) ){
		// exit as sensei will tell the user to complete the previous lesson
		return $drip_status;
	}

	// get the previous lessons completion date
	$activitiy_query = array( 'post_id' => $prerequisite_lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date_gmt' );
	$user_lesson_end_date_gmt =  WooThemes_Sensei_Utils::sensei_get_activity_value( $activitiy_query  );

	// get the dateTime objects
	$today = new DateTime();
	$lesson_end = new DateTime($user_lesson_end_date_gmt);

	// create a date interval object to determine when the lesson should become available
	$unit_type_first_letter_uppercase = strtoupper( substr($unit_type, 0, 1) ) ; 
	$interval_to_lesson_availablilty = new DateInterval('P'.$unit_amount.$unit_type_first_letter_uppercase );

	// create an object which the interval will be added to and add the interval
	$lesson_becomes_available_date = new DateTime($user_lesson_end_date_gmt);
	$lesson_becomes_available_date->add( $interval_to_lesson_availablilty );
	
	// compare dates
	// if lesson_becomes_available_date is greater than the today the drip date ist still active and lesson content should be hidden
	if( $lesson_becomes_available_date > $today  ){
		$drip_status  = true;
	}

	// finaly return $drip_status
	return $drip_status;

} //  end is_dynamic_drip_active

/**
* Replace post content with settings or filtered message
* This function actson the title , content , ebmbeded video and quiz
* 
* @since 1.0.0
* @param  WP_Post $lesson
* @param  string $formatted_message a varialbe containing shortcodes options: [date] 
* @return WP_Post $lesson
*/

public function make_lesson_unavailable( $lesson , $formated_message){
	// ensure all things are in place before proceeding
	if( empty($lesson) || 'lesson' !== $lesson->post_type || empty( $lesson->ID ) ){
		return false;
	}

	// get the lessons drip data if any 
	$lesson_drip_data = get_post_meta( $lesson->ID, '_sensei_drip_content', true );

	if( ! $this->is_lesson_drip_active( $lesson , $lesson_drip_data ) ){
		// if the the drip is not active ignore it
		return $lesson;
	}

	//get the compiled message text
	$parsed_message = $this->get_drip_type_message( $lesson , $lesson_drip_data , $formated_message );
	
	// go through all the keys to replace the content and the excerpt
	foreach ( $lesson as $key => $value ) {
		//change both the post content and the excerpt
		if( $key === 'post_content' || $key === 'post_excerpt' ){	
			/**
			 * Filter a customise the message user will see when content is not available.
			 *
			 * @since 1.0.0
			 *
			 * @param string        $drip_message the message
			 */
			$lesson->$key = apply_filters( 'sensei_content_drip_lesson_message', $parsed_message );  
		}
	}

	//disable the current lessons video
	remove_all_actions( 'sensei_lesson_video' );

	//hide the lesson quiz notice and quiz buttons 
	remove_all_actions( 'sensei_lesson_quiz_meta' );

	// append a title message next for content that's dripped
	add_filter('the_title', array( $this ,'add_single_title_text'), 10, 1);

	// returh the lesson with changed content 
	return $lesson;

} // end make_lesson_unavailable


/**
* Append information to the single lesson title if the lesson content is dripped
* 
* @since 1.0.0
* @param string $title 
* @param string $id lesson post id
* @return string $title
*/
public function add_single_title_text( $title ){
	return $title. $this->title_append_text;
}


/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param WP_Post $lesson 
* @param array $dripped_data array( drip_type, dript_detail  ) ;
* @param string $formatted_message possibly contains shortcodes
* @return bool $dripped
*/
public function get_drip_type_message( $lesson , $lesson_drip_data ,  $formatted_message ){
	
	// setup the default message in case no data was paassed in
	$message = 'Content hidden by the author of this lesson' ;

	//check that the correct data has been passed
	if( empty($lesson) || 'lesson' !== $lesson->post_type 
		|| empty( $lesson->ID ) || empty($formatted_message) || empty( $lesson_drip_data )  ){
		// return the formated message as this could not be replaced
		return $message;
	}
	
	if( 'absolute'=== $lesson_drip_data['drip_type'] ){
		// call the absolute drip type message creator function which creates a message dependant on the date
		$message = $this->get_absolute_drip_type_message(  $formatted_message , $lesson_drip_data['drip_details']  );
	}elseif( 'dynamic' === $lesson_drip_data['drip_type']){
		// call the dynamic drip type message creator function which creates a message dependant on the date
		$message = $this->get_dynamic_drip_type_message( $lesson_drip_data['drip_details']  );
	}

	// return the changed message
	return $message;
}

/**
* Absolute driptype: converting the formatted messag into a standard string depending on the details passed in
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function get_absolute_drip_type_message( $formatted_message , $lesson_drip_date ){

	$absolute_drip_type_message = '';

	if( strpos( $formatted_message, '[date]') ){
		$absolute_drip_type_message=  str_replace('[date]', $lesson_drip_date , $formatted_message ) ;
	}else{
		$absolute_drip_type_message = $formatted_message . ' ' . $lesson_drip_date; 
	}

	return $absolute_drip_type_message;
}

/**
* dynamic driptype: converting the formatted messag into a standard string depending on the details passed in
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function get_dynamic_drip_type_message( $drip_details ){

	$dynamic_drip_type_message = '';

	// get the array data
	$unit_amount = $drip_details['unit-amount'];
	$unit_type = $drip_details['unit-type'];

	// plural or singular unit typ ?
	$unit_plural  =   $unit_amount > 1 ? 's': '';
	$unit_type = $unit_type.$unit_plural ;

	// setup find and replace arrays
	$replace = array( $unit_amount, $unit_type  );
	$find = array( '[unit-amount]', '[unit-type]' );

	// replace string content
	$dynamic_drip_type_message =  str_replace($find , $replace , $this->dynamic_formatted_message );

	return $dynamic_drip_type_message;
}


} // Scd_ext_lesson_frontend class 