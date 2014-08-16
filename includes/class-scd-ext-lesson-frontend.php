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
	$this->drip_message = 'sorry drip, drip , drip...'; 
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
		if ( $this->is_lesson_dripped( $lesson ) ){
			// change the lesson content accordingly
			$lesson =  $this->replace_content( $lesson );
		}
	}

	return $lessons;
} // end lessons_drip_filter

/**
* Replace post content with settings or filtered message
* 
* @since 1.0.0
* @param  WP_Post $lesson
* @return WP_Post $lesson
*/

public function replace_content( $lesson ){
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
		$lesson->$key = apply_filters( 'sensei_content_drip_lesson_message', $this->drip_message );  

		}
	}
	return $lesson;

} // end replace_content

/**
* Check if  the lesson can be made available to the the user at this point
* according to the drip meta data
* 
* @since 1.0.0
* @param  WP_Post $lesson 
* @return bool $dripped
*/

public function is_lesson_dripped( $lesson ){

	$dripped = true;

	// check the post data and alter $dripped

	return $dripped;

} // end is_lesson_dripped

} // Scd_ext_lesson_frontend class 