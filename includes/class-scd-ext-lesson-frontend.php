<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Sensei Content Drip ( scd ) Exctension Class
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
 */


class Scd_ext_lesson_frontend {

/**
* constructor function
*
* @uses add_filter
*/
public function __construct(){

	// hook int all post of type lesson to determin if they are 
	//add_filter('the_posts', array( $this, 'replace_dripped_lessons_content' ) );
}// end __construct()


/**
* replace_dripped_lessons_content, alters the content of all lesson with their 
* respecitve drip content replacement message. 
* 
* @since 1.0.0
* @param array $posts
* @return array $posts
* @uses the_posts()
*/

public function replace_dripped_lessons_content( $posts ){

	// this should only apply to the front end on single course and lesson pages
	if( is_admin() || 
		! ( is_singular('lesson') || is_singular('course') )  ){
		return $posts;	
	}
	 
	$message = 'sorry drip, drip , drip...';

	var_dump( $posts );

	echo "hello";

	return $posts;

// if ! post type lesson -- retrn $posts

// if settings show excerpt

// loop through lessons 
	// if drip is active change content to message

} // end hide_lesson_content

} // Scd_ext_lesson_frontend class 