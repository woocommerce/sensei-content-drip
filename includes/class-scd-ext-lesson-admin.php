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
 * - add_leson_content_drip_meta_box
 * - content_drip_lesson_meta_content( $lesson )
 * - save_course_drip_meta_box_data
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
	add_action('add_meta_boxes', array( $this, 'add_leson_content_drip_meta_box' ) );

	// save the meta box
	add_action('save_post', array( $this, 'save_course_drip_meta_box_data' ) );

	// admin_notices
	add_action( 'admin_notices', array( $this, 'lesson_admin_notices' ) );	

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
	global $post;

	// get the post meta data
	$post_content_drip_data = get_post_meta( $post->ID, '_sensei_drip_content' , true);
	$selected_drip_type = isset( $post_content_drip_data['drip_type'] ) ? $post_content_drip_data['drip_type'] : 'none';

	// show or hide the options for each content_drip_type
	$absolute_hidden_class = ( 'absolute' == $selected_drip_type ) ? '' : 'hidden'; 
	$dymaic_hidden_class   = ( 'dynamic'  == $selected_drip_type ) ? '' : 'hidden'; 

	//get the absolute date stored field value
	$absolute_date_value = ( 'absolute' == $selected_drip_type ) ?  $post_content_drip_data['drip_details'] : '';
	
	// Nonce field
	wp_nonce_field( -1, 'woo_' . $this->_token . '_noonce');

?>
	<p><?php _e('How would you like this lesson to be dripped ?', 'sensei-content-drip'); ?></p>
	<p><select name='sdc-lesson-drip-type' class="sdc-lesson-drip-type">
		<option <?php selected( 'none', $selected_drip_type  ) ?> value="none"> <?php _e('None', 'sensei-content-drip'); ?></option>
		<option <?php selected( 'absolute', $selected_drip_type  ) ?> value="absolute"> <?php _e('Specific date ', 'sensei-content-drip'); ?>  </option>
		<option <?php selected( 'dynamic', $selected_drip_type  ) ?> value="dynamic"> <?php _e('After previous lessons', 'sensei-content-drip'); ?> </option>
	</select></p>
	
	<p><div class="dripTypeOptions absolute <?php echo $absolute_hidden_class;?> ">
		<p><span class='description'><?php _e('Select the date on which this lesson should become available ?', 'sensei-content-drip'); ?></span></p>
		<input type="date" id="datepicker" name="absolute[datepicker]" value="<?php echo $absolute_date_value  ;?>" class="absolute-datepicker" />
	</div></p>
	<p> <div class="dripTypeOptions dynamic <?php echo $dymaic_hidden_class;?> "> 
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

/**
* save_course_drip_meta_box_data, listens to the save_post hook and saves the data accordingly
*
* @since 1.0.0
* @param string $post_id
*/

public function save_course_drip_meta_box_data( $post_id ) {
	global $post, $messages;

	 // verify if this is an auto save routine. 
  	 // If it is our form has not been submitted, so we dont want to do anything
  	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
    	return $post_id;	
    } 
      

	/* Verify the nonce before proceeding. */
	if ( get_post_type() != 'lesson'  
		 || !wp_verify_nonce( $_POST['woo_' . $this->_token . '_noonce'] ) 
		 || !isset( $_POST['sdc-lesson-drip-type'] ) ) {

		return $post_id;
	}
	
	// retrieve the existing data 
	$post_content_drip_data = get_post_meta( $post_id, '_sensei_drip_content', true );

	// default data structure
	$default = array(
			'drip_type' => 'none' , /* options: none , absolute ,dynamic */ 
			'drip_details' => null  /* options: abosulte => unixDateStamp , dymaic=> days   */ 
	);

	// new data holding array
	$new_data = array();

	// if none is selected and the previous data was also set to none return
	if ( 'none' === $_POST['sdc-lesson-drip-type'] ){
		
		// new data should be that same as default
		$new_data = $default;
		
	} elseif(  'absolute' === $_POST['sdc-lesson-drip-type'] ){
		// login to deal with and exact date

		// convert selected date to a unix time stamp
		// incoming Format:  yyyy/mm/dd

		$date_string = $_POST['absolute']['datepicker'];

		if( empty( $date_string )  ){

			$notices = array( 'error' => __('Please select a date for your chosen option "On a specifcic date" ',  'sensei-content-drip' ) );
			update_option(  '_sensei_content_drip_lesson_notice' , $notices   );
			
			return $post_id;
		}
		
		$new_data['drip_type'] = 'absolute';
		$new_data['drip_details'] = $date_string;

	}elseif( 'dynamic' === $_POST['sdc-lesson-drip-type']   ){
		// logic to deal with a dynamic date

		// convert user selection to the number of days and save it

		// set the met data to save
		$new_data['drip_type'] = 'dynamic';
	}

	// update the meta data
	update_post_meta( $post_id ,'_sensei_drip_content', $new_data );

	return $post_id;

} // end save_course_drip_meta_box_data

/**
* lesson_admin_notices 
* edit / new messages , loop through the messasges save in the options table and display theme here
* 
* @since 1.0.0
* @param array $posts
* @return array $posts
* @uses the_posts()
*/

public function lesson_admin_notices(){

	// retrieve the notice array 
	$notice = get_option('_sensei_content_drip_lesson_notice');

	// if there are not notices to display exit
	if( empty($notice) ){
		return ;
	}

	// print all notices
	foreach ($notice as $type => $message) {
			echo '<div class="'.$type.' fade"><p>Content Drip: ' . $message. '</p></div>';
	}

	// clear all notices
	delete_option('_sensei_content_drip_lesson_notice');
	
} // end lesson_admin_notices



} // Scd_ext_lesson_frontend class 