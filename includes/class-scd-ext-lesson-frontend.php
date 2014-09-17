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
	global $woo_sensei_content_drip;
	// set a formated string
	$this->message_format =  $woo_sensei_content_drip->settings->get_setting( 'scd_drip_message' ) ; 

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
		if ( $this->is_lesson_drip_active( $lesson->ID ) ){
			// change the lesson content accordingly
			// todo : pass in the content instad of the whole lesson so tha function simply does one thing
			$lesson =  $this->replace_lesson_content( $lesson );
		}
	}

	return $lessons;
} // end lessons_drip_filter

/**
* Replace post content with settings or filtered message
* This function actson the title , content , ebmbeded video and quiz
* 
* @since 1.0.0
* @param  WP_Post $lesson
* @param  string $formatted_message a varialbe containing shortcodes options: [date] 
* @return WP_Post $lesson
*/

public function replace_lesson_content( $lesson ){
	$new_content = '';

	// ensure all things are in place before proceeding
	if( empty($lesson) || 'lesson' !== $lesson->post_type || empty( $lesson->ID ) ){
		return false;
	}

	//get the compiled message text
	$new_content = $this->get_drip_type_message( $lesson->ID );

	// wrap the message in sensei notice
	$new_content = '<div class="sensei-message info">' . $new_content . '</div>' ;
	
	/**
	 * Filter a customise the message user will see when content is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $drip_message the message
	 */
	$new_content= apply_filters( 'sensei_content_drip_lesson_message', $new_content );  

	$lesson->post_content = $new_content;
	$lesson->post_excerpt = $new_content;

	//disable the current lessons video
	remove_all_actions( 'sensei_lesson_video' );

	//hide the lesson quiz notice and quiz buttons 
	remove_all_actions( 'sensei_lesson_quiz_meta' );

	// returh the lesson with changed content 
	return $lesson;

} // end replace_lesson_content


/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function is_lesson_drip_active( $lesson_id ){

	$dripped = false;

	// return drip not active for the fllowing conditions
	if( is_super_admin() || empty( $lesson_id ) || 'lesson' !== get_post_type( $lesson_id ) ){
		return $dripped;
	}
	
	// get the lessons drip data if any 
	$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true );

	// check if the content should be dripped
	if( empty( $drip_type ) || 'none' === $drip_type ) {
		$dripped = false;
	}elseif( 'absolute' === $drip_type  ){
		$dripped = $this->is_absolute_drip_active( $lesson_id  ); 
	}elseif( 'dynamic' === $drip_type ){
		$dripped = $this->is_dynamic_drip_active( $lesson_id  );
	}

	/**
	*	filter scd_is_drip_active
	*
	*	@param boolean $dripped
	*   filter the bolean value returned. The value tells us if a drip is active on the given lesson
	*/
	return  apply_filters('scd_is_drip_active' , $dripped , $lesson_id );

} // end is_lesson_dripped



/**
* Check specifically if the absolute drip is active on this lesson
* depending only on the date
* 
* @since 1.0.0
* @param  array $dripped_data
* @return bool $active
*/
public function is_absolute_drip_active( $lesson_id ){
	// setup the default drip status 
	$drip_status = false;
	

	// get the lessons data
	$dripped_data =  Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );

	// confirm that all needed data is in place otherwise return false
	if( empty( $dripped_data ) || !isset( $dripped_data['_sensei_content_drip_type'] ) 
		|| !isset( $dripped_data['_sensei_content_drip_details_date'] ) || 'absolute' !== $dripped_data['_sensei_content_drip_type'] ) {
		return $drip_status;
	}

	// convert string dates to date ojbect
	$lesson_drip_date = new DateTime( $dripped_data['_sensei_content_drip_details_date'] );
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
* @param string $lesson_id
* @return bool $active
*/
public function is_dynamic_drip_active( $lesson_id ){
	global $woothemes_sensei ;

	// setup the default drip status 
	$drip_status = false;

	// get the lessons data
	$dripped_data =  Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );

	// confirm that all needed data is in place otherwise this content will be available 
	if( empty( $dripped_data ) 
		|| empty( $dripped_data['_sensei_content_drip_details_date_unit_type'] )   
		|| empty( $dripped_data['_sensei_content_drip_details_date_unit_amount'] ) 
		|| empty( $dripped_data['_sensei_content_drip_dynamic_pre_lesson_id'] ) ){  
		
		// deafult set to false
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
	$unit_type  =  $dripped_data['_sensei_content_drip_details_date_unit_type'];
	$unit_amount = $dripped_data['_sensei_content_drip_details_date_unit_amount'];
	$drip_pre_lesson_id = $dripped_data['_sensei_content_drip_dynamic_pre_lesson_id'];

	// if the data is not correct then the drip lesson should be shown
	if( !in_array($unit_type, array( 'day','week' ,'month' ) ) || ! is_numeric( $unit_amount ) 
		|| empty( $drip_pre_lesson_id )   ){
		// trigger an error for the user to understand what just went wrong so they can tell support what happend
		return $drip_status;
	}
	
	// if the user has not complted the previous exit
	if( !WooThemes_Sensei_Utils::user_completed_lesson( $drip_pre_lesson_id , $user_id ) ){
		// exit as sensei will tell the user to complete the previous lesson
		return $drip_status;
	}

	$lesson_becomes_available_date = $this->get_dynamic_lesson_available_date( $lesson_id );
	
	// get todays date	
	$today = new DateTime();	
	
	// compare dates
	// if lesson_becomes_available_date is greater than the today the drip date ist still active and lesson content should be hidden
	if( $lesson_becomes_available_date > $today  ){
		$drip_status  = true;
	}

	// finaly return $drip_status
	return $drip_status;

} //  end is_dynamic_drip_active


/**
* Determine when the lesson becomes available
* 
* @since 1.0.0
* @param  string $lesson_id
* @return DateTime $lesson_becomes_available_date
*/
public function get_dynamic_lesson_available_date( $lesson_id ){

	// get the lessons data
	$dripped_data =  Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );
	
	// get the user details
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	// get the drip details array data
	$unit_type  =  $dripped_data['_sensei_content_drip_details_date_unit_type'];
	$unit_amount = $dripped_data['_sensei_content_drip_details_date_unit_amount'];
	$drip_pre_lesson_id = $dripped_data['_sensei_content_drip_dynamic_pre_lesson_id'];
	
	// get the previous lessons completion date
	$activitiy_query = array( 'post_id' => $drip_pre_lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date_gmt' );
	$user_lesson_end_date_gmt =  WooThemes_Sensei_Utils::sensei_get_activity_value( $activitiy_query  );

	// create a date interval object to determine when the lesson should become available
	$unit_type_first_letter_uppercase = strtoupper( substr($unit_type, 0, 1) ) ; 
	$interval_to_lesson_availablilty = new DateInterval('P'.$unit_amount.$unit_type_first_letter_uppercase );

	// get the dateTime objects
	$lesson_end = new DateTime($user_lesson_end_date_gmt);

	// create an object which the interval will be added to and add the interval
	$lesson_becomes_available_date = new DateTime($user_lesson_end_date_gmt);

	return $lesson_becomes_available_date->add( $interval_to_lesson_availablilty );

}// end get_dynamic_lesson_available_date

/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param string $lesson_id 
* @return bool $dripped
*/
public function get_drip_type_message( $lesson_id ){
	
	// setup the default message in case no data was paassed in
	$message = 'Content hidden by the author of this lesson' ;

	//check that the correct data has been passed
	if( empty( $lesson_id) ){
		// just rerturn the simple message as the exact message can not be dtermined without the ID
		return $message;
	}

	$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true );

	if( 'absolute'=== $drip_type ){

		// call the absolute drip type message creator function which creates a message dependant on the date
		$message = $this->generate_absolute_drip_type_message( $lesson_id );
	
	}elseif( 'dynamic' === $drip_type ){
		// call the dynamic drip type message creator function which creates a message dependant on the date
		$message = $this->generate_dynamic_drip_type_message( $lesson_id );
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

public function generate_absolute_drip_type_message( $lesson_id ){

	$absolute_drip_type_message = '';

	// get this lessons drip data
	$lesson_drip_date =  get_post_meta( $lesson_id , '_sensei_content_drip_details_date' , true );
	
	// replace the shortcode in the class message_format property set in the constructor
	if( strpos( $this->message_format , '[date]') ){
		$absolute_drip_type_message =  str_replace( '[date]', $lesson_drip_date , $this->message_format ) ;
	}else{
		$absolute_drip_type_message = $this->message_format . ' ' . $lesson_drip_date; 
	}

	return $absolute_drip_type_message;
} // end generate_absolute_drip_type_message

/**
* dynamic driptype: converting the formatted messag into a standard string depending on the details passed in
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/
public function generate_dynamic_drip_type_message( $lesson_id ){

	$dynamic_drip_type_message = '';

	$lesson_available_date = $this->get_dynamic_lesson_available_date( $lesson_id );
 	
	$formatted_date =  $lesson_available_date->format('l jS F Y');

	// replace string content in the class message_format property set in the constructor
	$dynamic_drip_type_message =  str_replace('[date]' , $formatted_date , $this->message_format );

	return $dynamic_drip_type_message;
}// end generate_dynamic_drip_type_message

} // Scd_ext_lesson_frontend class 