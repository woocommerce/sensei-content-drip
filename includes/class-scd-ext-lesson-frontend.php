<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sensei Content Drip ( scd ) Extension Lesson Frontend
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
 * - __construct
 * - lesson_content_drip_filter
 * - get_lesson_with_updated_content
 * - get_drip_type_message
 * - generate_absolute_drip_type_message
 * - generate_dynamic_drip_type_message
 * - get_lesson_drip_type
 */

class Scd_Ext_Lesson_Frontend {

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
 * @uses add_filter 'the_posts'
  */
public function __construct(){
	
	// set a formatted  message shown to user when the content has not yet dripped
	$defaultMessage = __( 'This lesson will only become available on [date].', 'sensei-content-drip' ) ;
	$settingsMessage =  Sensei_Content_Drip()->settings->get_setting( 'scd_drip_message' ) ; 
	$this->message_format = empty( $settingsMessage ) ? $defaultMessage : $settingsMessage ; 

	// hook int all post of type lesson to determine if they should be
	add_filter('the_posts', array( $this, 'lesson_content_drip_filter' ), 1 );

}// end __construct()


/**
 * lesson_content_drip_filter, loops through each post page
 * to confirm if ths content should be hidden
 *
 * @since 1.0.0
 * @param array $lessons
 * @return array $lessons
 * @uses the_posts()
 */
public function lesson_content_drip_filter( $lessons ){

	// this should only apply to the front end on single course and lesson pages
	if( is_admin() ||  empty( $lessons ) ){
		return $lessons;	
	}

	//the first post in the array should be of post type lesson
	if( 'lesson' !== $lessons[0]->post_type  ){
		return $lessons;
	}
	 	
	// loop through each post and replace the content
	foreach ($lessons as $index => $lesson ) {
		if ( Sensei_Content_Drip()->access_control->is_lesson_access_blocked( $lesson->ID ) ){
			// change the lesson content accordingly
			$lessons[ $index ] =  $this->get_lesson_with_updated_content( $lesson );
		}
	} // end for each

	return $lessons;

} // end lessons_drip_filter

/**
 * Replace post content with settings or filtered message
 * This function acts on the title , content , embedded video and quiz
 *
 * @since 1.0.0
 * @param  WP_Post $lesson
 * @return WP_Post $lesson
 */
public function get_lesson_with_updated_content( $lesson ) {

	// ensure all things are in place before proceeding
	if( empty( $lesson ) ){

		return $lesson;

	}

	//get the compiled message text
	$new_content = $this->get_drip_type_message( $lesson->ID );

	// wrap the message in sensei notice
	$new_content = '<div class="sensei-message info">' . esc_html( $new_content ) . '</div>' ;
	
	/**
	 * Filter the message a user will see when content is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $drip_message the message
	 */
	$new_content = apply_filters( 'sensei_content_drip_lesson_message', $new_content );

    $lesson->post_content = '<p>' . wp_trim_words( $lesson->post_content , 20 ) . '</p>' . $new_content;

	// set the excerpt to be a trimmed down version of the full content if it is empty
	if( empty( $lesson->post_excerpt )  ){

		$lesson->post_excerpt = '<p>' . wp_trim_words( $lesson->post_content , 20 ) . '</p>' . $new_content;

	}else{

		$lesson->post_excerpt = '<p>' .  $lesson->post_excerpt  . '... </p>' . $new_content;

	}

	//disable the current lessons video
	remove_all_actions( 'sensei_lesson_video' );

	//hide the lesson quiz notice and quiz buttons 
	remove_all_actions( 'sensei_lesson_quiz_meta' );

	// return the lesson with changed content
	return $lesson;

} // end replace_lesson_content


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
		// just return the simple message as the exact message can not be determined without the ID
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
 * Absolute drip type: converting the formatted messages into a standard string depending on the details passed in
 *
 * @since 1.0.0
 * @param  int $lesson_id
 * @return bool $dripped
 */

public function generate_absolute_drip_type_message( $lesson_id ){

	$absolute_drip_type_message = '';

	// get this lessons drip data
	$lesson_drip_date =  new DateTime( get_post_meta( $lesson_id , '_sensei_content_drip_details_date' , true ) );
	$formatted_date =  date_i18n( get_option( 'date_format' ), $lesson_drip_date->getTimestamp() );
	// replace the shortcode in the class message_format property set in the constructor
	if( strpos( $this->message_format , '[date]') ){
		$absolute_drip_type_message =  str_replace( '[date]', $formatted_date , $this->message_format ) ;
	}else{
		$absolute_drip_type_message = $this->message_format . ' ' . $formatted_date; 
	}

	return $absolute_drip_type_message;

} // end generate_absolute_drip_type_message

/**
 * dynamic drip type: converting the formatted message into a standard string depending on the details passed in
 *
 * @since 1.0.0
 * @param  int $lesson_id
 * @return bool $dripped
 */
public function generate_dynamic_drip_type_message( $lesson_id ){

	// get the user details
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	$dynamic_drip_type_message = '';

	$lesson_available_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id , $user_id );
	$formatted_date =  date_i18n( get_option( 'date_format' ), $lesson_available_date->getTimestamp() );

	// replace string content in the class message_format property set in the constructor
	$dynamic_drip_type_message =  str_replace('[date]' , $formatted_date , $this->message_format );

	return $dynamic_drip_type_message;

}// end generate_dynamic_drip_type_message

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

	// retrieve the drip type from the lesson
	$drip_type = get_post_meta( $lesson_id , '_sensei_content_drip_type', true ) ;

	// send back the type string
	return  empty( $drip_type ) ? 'none' : $drip_type;

} // end get_drip_type

} // Scd_ext_lesson_frontend class