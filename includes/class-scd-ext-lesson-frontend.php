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
	$this->drip_formatted_message = "This lesson will become available on: [date]"; 

	// hook int all post of type lesson to determin if they are 
	add_filter('the_posts', array( $this, 'lessons_drip_filter' ) );

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
			$lesson =  $this->replace_content( $lesson );
		}
	}

	return $lessons;
} // end lessons_drip_filter

/**
* Replace post content with settings or filtered message
* this function has to be after if
* 
* @since 1.0.0
* @param  WP_Post $lesson
* @param  string $formatted_message a varialbe containing shortcodes options: [date] 
* @return WP_Post $lesson
*/

public function replace_content( $lesson , $formated_message){
	// ensure all things are in place before proceeding
	if( empty($lesson) || 'lesson' !== $lesson->post_type || empty( $lesson->ID ) ){
		return false;
	}

	// get the lessons drip data if any 
	$dripped_data = get_post_meta( $lesson->ID, '_sensei_drip_content', true );

	if( ! $this->is_lesson_drip_active( $lesson , $drip_data ) ){
		// if the the drip is not active ignore it
		return $lesson;
	}

	//get the compiled message text
	$parsed_message = get_drip_type_message( $lesson , $dripped_data , $formated_message );
	
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

	// returh the lesson with changed content 
	return $lesson;

} // end replace_content


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

public function get_drip_type_message( $lesson , $drip_data ,  $formatted_message ){
	
	// setup the default message in case no data was paassed in
	$message = 'Content hidden by the author of this lesson' ;

	//check that the correct data has been passed
	if( empty($lesson) || 'lesson' !== $lesson->post_type 
		|| empty( $lesson->ID ) || empty($formatted_message) || empty( $drip_data )  ){
		// return the formated message as this could not be replaced
		return $message;
	}
	

	if( 'absolute'=== $drip_data['drip_type'] ){
		// call the absolute drip type message creator function which creates a message dependant on the date
		$message = _get_absolute_drip_type_message(  $formatted_message , $drip_data['dript_detail']  );
	}elseif( 'dynamic' ){
		// call the dynamic drip type message creator function which creates a message dependant on the date
		$message = _get_dynamic_drip_type_message( $formatted_message , $drip_data['dript_detail']  );
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

public function _get_absolute_drip_type_message( $formatted_message , $drip_detail ){

	if( strpos( $formatted_message, '[date]') ){
		return str_replace('[date]', $drip_detail , $formatted_message ) ;
	}else{
		return $formatted_message . ' ' . $dript_detail; 
	}

}

/**
* dynamic driptype: converting the formatted messag into a standard string depending on the details passed in
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function _get_dynamic_drip_type_message( $lesson ){
	
}

/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function is_lesson_drip_active( $lesson ){

	$dripped = true;

	// return drip not active for the fllowing conditions
	if( is_super_admin() || empty($lesson) || 'lesson' !== $lesson->post_type || empty( $lesson->ID ) ){
		return false;
	}

	// get the lessons drip data if any 
	$dripped_data = get_post_meta( $lesson->ID , '_sensei_drip_content', true );
	
	// check if the content should be dripped
	if( empty( $dripped_data ) || !isset( $dripped_data['drip_type'] ) || 'none' === $dripped_data['drip_type'] ) {
		return false;
	}

	// check the post data and alter $dripped
	return $dripped;
} // end is_lesson_dripped

} // Scd_ext_lesson_frontend class 