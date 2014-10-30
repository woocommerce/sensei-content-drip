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
 * __construct
 * lessons_drip_filter
 * replace_lesson_content
 * is_lesson_drip_active
 * is_absolute_drip_active
 * is_dynamic_drip_active
 * get_drip_type_message
 * generate_absolute_drip_type_message
 * generate_dynamic_drip_type_message
 * get_date_format_string
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
	
	// set a formatted  message shown to user when the content has not yet dripped
	$defaultMessage = __( 'This lesson will only become available on [date].', 'sensei-content-drip' ) ;
	$settingsMessage =  Sensei_Content_Drip()->settings->get_setting( 'scd_drip_message' ) ; 
	$this->message_format = empty( $settingsMessage ) ? $defaultMessage : $settingsMessage ; 

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
			// todo : pass in the content instead of the whole lesson so tha function simply does one thing
			$lesson =  $this->replace_lesson_content( $lesson );
		}
	}

	return $lessons;
} // end lessons_drip_filter

/**
* Replace post content with settings or filtered message
* This function acts on the title , content , embedded video and quiz
* 
* @since 1.0.0
* @param  WP_Post $lesson
* @param  string $formatted_message a variable containing shortcodes options: [date]
* @return WP_Post $lesson
*/

public function replace_lesson_content( $lesson ) {

	// ensure all things are in place before proceeding
	if( empty($lesson) || 'lesson' !== $lesson->post_type || empty( $lesson->ID ) ){
		return false;
	}

	//get the compiled message text
	$new_content = $this->get_drip_type_message( $lesson->ID );

	// wrap the message in sensei notice
	$new_content = '<div class="sensei-message info">' . esc_html( $new_content ) . '</div>' ;
	
	/**
	 * Filter a customise the message user will see when content is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $drip_message the message
	 */
	$new_content= esc_html( apply_filters( 'sensei_content_drip_lesson_message', $new_content ) );

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

	// get the user details
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	// convert string dates to date ojbect
	$lesson_drip_date = Sensei_Content_Drip()->utils->get_lesson_drip_date( $lesson_id , $user_id );
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
	global $woothemes_sensei;
	// setup the default drip status 
	$drip_status = false;

	// get the lessons data
	$dripped_data = Sensei_Content_Drip()->lesson_admin->get_lesson_drip_data( $lesson_id );

	// confirm that all needed data is in place otherwise this content will be available 
	if( empty( $dripped_data ) 
		|| empty( $dripped_data['_sensei_content_drip_details_date_unit_type'] )   
		|| empty( $dripped_data['_sensei_content_drip_details_date_unit_amount'] ) ){  
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

	// if the data is not correct then the drip lesson should be shown
	if( !in_array($unit_type, array( 'day','week' ,'month' ) ) || ! is_numeric( $unit_amount ) ){
		return $drip_status;
	}
	
	$lesson_becomes_available_date =  Sensei_Content_Drip()->utils->get_lesson_drip_date( $lesson_id , $user_id );
		
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
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param string $lesson_id 
* @return bool $dripped
*/
public function get_drip_type_message( $lesson_id ){

    $message = '';

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
	$lesson_drip_date =  new DateTime( get_post_meta( $lesson_id , '_sensei_content_drip_details_date' , true ) );
	$formatted_date =  $lesson_drip_date->format( $this->get_date_format_string() );
	// replace the shortcode in the class message_format property set in the constructor
	if( strpos( $this->message_format , '[date]') ){
		$absolute_drip_type_message =  str_replace( '[date]', $formatted_date , $this->message_format ) ;
	}else{
		$absolute_drip_type_message = $this->message_format . ' ' . $formatted_date; 
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

	// get the user details
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	$dynamic_drip_type_message = '';

	$lesson_available_date = Sensei_Content_Drip()->utils->get_lesson_drip_date( $lesson_id , $user_id );
 	
	$formatted_date =  $lesson_available_date->format( $this->get_date_format_string() );

	// replace string content in the class message_format property set in the constructor
	$dynamic_drip_type_message =  str_replace('[date]' , $formatted_date , $this->message_format );

	return $dynamic_drip_type_message;
}// end generate_dynamic_drip_type_message

/**
* get the date format and allow the user to filter it
* 
* @since 1.0.0
* @return string $date_format
*/
public function get_date_format_string(){
	$date_format = 'l jS F Y';
	/**
	* filter scd_drip_message_date_format
	* @param string 
	*/
	return apply_filters( 'scd_drip_message_date_format' , $date_format );

}//end get_date_format
} // Scd_ext_lesson_frontend class 