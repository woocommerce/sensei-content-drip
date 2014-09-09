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

} // end class Sensei_Scd_Extension_Utils