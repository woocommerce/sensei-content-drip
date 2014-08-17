<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Sensei Content Drip ( scd ) Exctension lesson admin class
 *
 * Thie class controls all admin functionaliy related to sensei lessons
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

class Scd_ext_lesson_admin {

/**
 * The token.
 * @var     string
 * @access  private
 * @since   1.0.0
 */
private $_token;

/**
* constructor function
*
* @uses add_filter
*/
public function __construct(){

	// set the plugin token for this class
	$this->_token = 'sensei_content_drip';

	// hook int all post of type lesson to determin if they are 
	add_filter('add_meta_boxes', array( $this, 'add_leson_content_drip_meta_box' ) );

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

public function add_leson_content_drip_meta_box( ){
	add_meta_box( 'content-drip-lesson', __('Drip Content','sensei-content-drip') , array( $this, 'content_drip_lesson_meta_content'  ), 'lesson' , 'side', 'default' , null  );

} // end add_leson_content_drip_meta_box

/**
* content_drip_lesson_meta_content , display the content inside the meta box
* 
* @since 1.0.0
* @param array $posts
* @return array $posts
* @uses the_posts()
*/

public function content_drip_lesson_meta_content(){
?>
	<p><?php _e('How would you like this lesson to be dripped ?', 'sensei-content-drip'); ?></p>
	<p><select name='sdc-lesson-drip-type' class="sdc-lesson-drip-type">
		<option  value="none"> <?php _e('Do not drip it', 'sensei-content-drip'); ?></option>
		<option  value="absolute"> <?php _e('On a specifcic date ', 'sensei-content-drip'); ?>  </option>
		<option  value="dynamic"> <?php _e('After the previous lessons completion', 'sensei-content-drip'); ?> </option>
	</select></p>
	
	<p><div class="dripTypeOptions absolute hidden">
		<p><span class='description'><?php _e('Select the date on which this lesson should become available ?', 'sensei-content-drip'); ?></span></p>
		<input type="date" id="datepicker" name="absolute[datepicker]" value="" class="absolute-datepicker" />
	</div></p>
	<p> <div class="dripTypeOptions dynamic hidden"> 
		<p><span class='description'><?php _e('How long after the completion of the previous lesson should this lesson become available ?', 'sensei-content-drip'); ?></span></p>
		<div id="dynamic-dripping-1" class='dynamic-dripping'>
			<input type='number' name='unit-amount-1' class='unit-amount' ></input>
			<select name='dynamic-time-unit-1' class="dynamic-time-unit">
				<option  value="day"> <?php _e('Day(s)', 'sensei-content-drip'); ?></option>
				<option  value="week"> <?php _e('Week(s)', 'sensei-content-drip'); ?> </option>
				<option  value="month"> <?php _e('Month(s)', 'sensei-content-drip'); ?>  </option>
			</select>
			<p>note: The course start date will be used, if you have not selected a lesson pre-requisite</p>
		</div>	
	</div></p>
<?php 
}

} // Scd_ext_lesson_frontend class 