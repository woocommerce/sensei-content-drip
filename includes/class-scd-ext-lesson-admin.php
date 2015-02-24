<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Sensei Content Drip ( scd ) Extension lesson admin class
 *
 * This class controls all admin functionality related to sensei lessons
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 * - __construct
 * - add_lesson_content_drip_meta_box
 * - add_column_heading
 * - add_column_data
 * - content_drip_lesson_meta_content
 * - get_course_lessons
 * - save_course_drip_meta_box_data
 * - lesson_admin_notices
 * - get_meta_field_keys
 * - save_lesson_drip_data
 * - get_lesson_drip_data
 * - delete_lesson_drip_data
 * - get_all_dripping_lessons
 */

class Scd_Ext_Lesson_Admin {
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

	//add view all lessons columns
	add_filter( 'manage_edit-lesson_columns', array( $this, 'add_column_heading' ), 20, 1 );
	add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 20, 2 );

	// hook int all post of type lesson to determin if they are 
	add_action('add_meta_boxes', array( $this, 'add_lesson_content_drip_meta_box' ) );

	// save the meta box
	add_action('save_post', array( $this, 'save_course_drip_meta_box_data' ) );

	// admin_notices
	add_action( 'admin_notices', array( $this, 'lesson_admin_notices' ) , 80 );	

}// end __construct()

/**
* add_lesson_content_drip_meta_box, hooking the meta box content into the edit lesson screen
*
* @since 1.0.0
* @return void
* @uses the_posts()
*/

public function add_lesson_content_drip_meta_box( ){
	add_meta_box( 'content-drip-lesson', __('Sensei Content Drip','sensei-content-drip') , array( $this, 'content_drip_lesson_meta_content'  ), 'lesson' , 'normal', 'default' , null  );

} // end add_lesson_content_drip_meta_box

/**
* Add a new column to the vew all lessons admin screen
* 
* @since 1.0.0
* @param array $columns
* @return array $columns
*/
public function add_column_heading( $columns ){
	$columns['scd_drip_schedule'] = _x( 'Drip Schedule', 'column name', 'sensei-content-drip' );
	return $columns;
} // end add_lesson_content_drip_meta_box


/**
 * Add data for our drip schedule custom column
 *
 * @since  1.0.0
 * @param  string $column_name
 * @param  int $id
 * @return void
 */
public function add_column_data ( $column_key, $lesson_id ) {

	// exit early if this is not the column we want
	if( 'scd_drip_schedule' != $column_key ){
		return;	
	}

	// get the lesson drip type
	$drip_type = Sensei_Content_Drip()->lesson_frontend->get_lesson_drip_type( $lesson_id );

	//generate the messages
	if('none'==$drip_type ){
		echo 'Immediately';
	}elseif('absolute' == $drip_type ){
		$lesson_set_date = get_post_meta( $lesson_id ,'_sensei_content_drip_details_date', true  );
		echo 'On '. $lesson_set_date;
	}elseif ( 'dynamic' == $drip_type ) {
		$unit_type  =  get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_type', true );  
		$unit_amount = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_amount', true );

		//setup the time period strings
		$time_period =  $unit_amount.' '.$unit_type;

		// append an s to the unit if it is more than 1
		if( $unit_amount > 1 ){
			$time_period .= 's';
		}

		// assemble and output
		echo 'After '. $time_period;
	}
}// end add_column_data


/**
* content_drip_lesson_meta_content , display the content inside the meta box
* 
* @since 1.0.0
* @return array $posts
* @uses the_posts()
*/
public function content_drip_lesson_meta_content(){

	global $post;

	// setup the forms value variable to be empty , this is to avoid php notices
	$selected_drip_type = '';
	$absolute_date_value = '';
	$selected_dynamic_time_unit_type = '';
	$dynamic_unit_amount = '';

	// get the lesson drip meta data
	$lesson_drip_data = $this->get_lesson_drip_data( $post->ID );

	// get the lessons meta data
	$lesson_pre_requisite = get_post_meta( $post->ID , '_lesson_prerequisite', true );
	$current_lesson_course = get_post_meta( $post->ID , '_lesson_course', true );

	//show nothing  if no course is selected
	if( empty( $current_lesson_course ) ){
		echo '<p>'. __( 'In order to use the content drip settings, please select a course for this lesson.', 'sensei-content-drip' ) . '</p>';
		// exit without displaying the rest of the settings
		return;
	}

	//set the selected drip type according to the meta data for this post
	$selected_drip_type = isset( $lesson_drip_data['_sensei_content_drip_type'] ) ? $lesson_drip_data['_sensei_content_drip_type'] :  'none' ;

	// setup the hidden classes and assisgn the needed data
	if( 'absolute' === $selected_drip_type ){
		$absolute_hidden_class = ''; 
		$dymaic_hidden_class   = 'hidden'; 
		
		//get the absolute date stored field value
		$absolute_date_value =  $lesson_drip_data['_sensei_content_drip_details_date'];

	}elseif( 'dynamic' === $selected_drip_type  ){
		$absolute_hidden_class = 'hidden'; 
		$dymaic_hidden_class   = ''; 

		// get the data array
		$selected_dynamic_time_unit_type = $lesson_drip_data['_sensei_content_drip_details_date_unit_type'];
		$dynamic_unit_amount = $lesson_drip_data['_sensei_content_drip_details_date_unit_amount'];
	}else{
		$absolute_hidden_class = 'hidden'; 
		$dymaic_hidden_class   = 'hidden'; 
	}
	
	// Nonce field
	wp_nonce_field( -1, 'woo_' . $this->_token . '_noonce');
?>
	<p><?php _e( 'When should this lesson become available?', 'sensei-content-drip' ); ?></p>
	<p><select name='sdc-lesson-drip-type' class="sdc-lesson-drip-type">
		<option <?php selected( 'none', $selected_drip_type  ) ?> value="none" class="none"> <?php _e( 'As soon as the course is started', 'sensei-content-drip' ); ?></option>
		<option <?php selected( 'absolute', $selected_drip_type  ) ?> value="absolute" class="absolute"> <?php _e( 'On a specific date', 'sensei-content-drip' ); ?>  </option>
		<?php 
			//does this lesson have a  pre-requisites lesson ?
			$has_pre_requisite = empty( $lesson_pre_requisite ) ? 'false'  : 'true' ; 
		?>
		<option data-has-pre="<?php esc_attr_e( $has_pre_requisite ); ?> " <?php selected( 'dynamic', $selected_drip_type  ); ?> value="dynamic"  class="dynamic"> <?php _e( 'A specific interval after the course start date', 'sensei-content-drip' ); ?> </option>
	</select></p>
	
	<p><div class="dripTypeOptions absolute <?php esc_attr_e( $absolute_hidden_class ); ?> ">
		<p><span class='description'><?php _e('Select the date on which this lesson should become available ?', 'sensei-content-drip'); ?></span></p>
		<input type="text" id="datepicker" name="absolute[datepicker]" value="<?php esc_attr_e( $absolute_date_value )  ;?>" class="absolute-datepicker" />
	</div></p>
	<p> 
		<div class="dripTypeOptions dynamic <?php esc_attr_e( $dymaic_hidden_class );?> ">

			
		<?php if( empty( $current_lesson_course ) ){ ?>
			<p>
				<?php _e( 'Please select a course for this lesson in order to use this drip type.', 'sensei-content-drip'  ); ?>
			</p>

		<?php }else{  ?>

			<div id="dynamic-dripping-1" class='dynamic-dripping'>
				<input type='number' name='dynamic-unit-amount[1]' class='unit-amount' value="<?php esc_attr_e( $dynamic_unit_amount ); ?>"  />
		
				<select name='dynamic-time-unit-type[1]' class="dynamic-time-unit">
					<option <?php selected( 'day', $selected_dynamic_time_unit_type );?> value="day"> <?php _e('Day(s)', 'sensei-content-drip'); ?></option>
					<option <?php selected( 'week', $selected_dynamic_time_unit_type );?>  value="week"> <?php _e('Week(s)', 'sensei-content-drip'); ?> </option>
					<option <?php selected( 'month', $selected_dynamic_time_unit_type );?>  value="month"> <?php _e('Month(s)', 'sensei-content-drip'); ?>  </option>
				</select>
			</div>	
		<?php }// end if  count( $related_lessons_array ) >0    ?> 
	</div> <!-- end dripTypeOptions -->
	</p>
<?php 

} // end content_drip_lesson_meta_content

/**
 * get_course_lessons .
 *
 * @access public
 * @param int $course_id (default: 0)
 * @param string $exclude
 * @return array WP_Post 
 */
public function get_course_lessons( $course_id = 0, $exclude = '' ) {

	$args = array(	'post_type' 		=> 'lesson',
						'numberposts' 		=> -1,
						'meta_key'        	=> '_order_' . $course_id,
						'orderby'         	=> 'meta_value_num date',
						'order'           	=> 'ASC',
						'exclude'			=> $exclude,
						'meta_query'		=> array(
							array(
								'key' => '_lesson_course',
								'value' => intval( $course_id ),
							),
						),
						'post_status'       => 'public',
						'suppress_filters' 	=> 0
						);

	$lessons = get_posts( $args );

	return $lessons;
} // End course_lessons()

/**
* save_course_drip_meta_box_data, listens to the save_post hook and saves the data accordingly
*
* @since 1.0.0
* @param string $post_id
* @return string $post_id
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
		 || !isset( $_POST['woo_' . $this->_token . '_noonce'] )
		 || !wp_verify_nonce( $_POST['woo_' . $this->_token . '_noonce'] ) 
		 || !isset( $_POST['sdc-lesson-drip-type'] ) ) {

		return $post_id;
	}
	
	// retrieve the existing data 
	$old_lesson_content_drip_data = $this->get_lesson_drip_data( $post_id );
	
	//new data holding array
	$new_data = array();

	// if none is selected and the previous data was also set to none return
	if( 'none' === $_POST['sdc-lesson-drip-type'] ){
		
		// new data should be that same as default
		$new_data  = array( '_sensei_content_drip_type' =>'none' );
		
	}elseif(  'absolute' === $_POST['sdc-lesson-drip-type'] ){
		// convert selected date to a unix time stamp
		// incoming Format:  yyyy/mm/dd
		$date_string = $_POST['absolute']['datepicker'];

		if( empty( $date_string )  ){
			// create the error message and add it to the database 
			$message = __('Please choose a date under the  "Absolute" select box.', 'sensei-content-drip' );
			update_option(  '_sensei_content_drip_lesson_notice' , array( 'error' => $message ) );
			
			// set the current user selection
			update_post_meta( $post_id ,'_sensei_content_drip_type', 'none' );
			
			return $post_id;
		}

		// set the meta data to be saves later
				// set the mets data to ready to pass it onto saving
		$new_data =  array( 
					'_sensei_content_drip_type' => 'absolute',
					'_sensei_content_drip_details_date' =>  $date_string,
				);

	}elseif( 'dynamic' === $_POST['sdc-lesson-drip-type']   ){

		// get the posted data valudes
		$date_unit_amount = $_POST['dynamic-unit-amount']['1'] ;	// number of units
		$date_unit_type = $_POST['dynamic-time-unit-type']['1'];	// unit type eg: months, weeks, days		

		// input validation
		$dynamic_save_error =  false;
		if( empty( $date_unit_amount ) || empty( $date_unit_type  ) ){

			$save_error_notices = array( 'error' => __('Please select the correct units for your chosen option "After previous lesson" .',  'sensei-content-drip' ) );
			$dynamic_save_error = true;
		
		}elseif( !is_numeric($date_unit_amount)  ){
			
			$save_error_notices = array( 'error' => __('Please enter a numberic unit number for your chosen option "After previous lesson" .',  'sensei-content-drip' ) );
			$dynamic_save_error = true;

		}

		// input error handling
		if( $dynamic_save_error ){
			update_option(  '_sensei_content_drip_lesson_notice' , $save_error_notices   );
			// set the current user selection
			update_post_meta( $post_id ,'_sensei_content_drip_type', 'none' );
			// exit with no further actions
			return $post_id;
		}

		// set the mets data to ready to pass it onto saving
		$new_data =  array( 
					'_sensei_content_drip_type' => 'dynamic',
					'_sensei_content_drip_details_date_unit_type' =>  $date_unit_type,
					'_sensei_content_drip_details_date_unit_amount' => $date_unit_amount,
				);
	}

	// update the meta data
	$this->save_lesson_drip_data( $post_id , $new_data  );

	return $post_id;

} // end save_course_drip_meta_box_data

/**
* lesson_admin_notices 
* edit / new messages , loop through the messages save in the options table and display theme here
* 
* @since 1.0.0
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
			$message =  $message . ' The content drip type was reset to "none".';
			echo '<div class="'. esc_attr( $type ) .' fade"><p>Sensei Content Drip '. $type .': ' . $message . '</p></div>';
	}

	// clear all notices
	delete_option('_sensei_content_drip_lesson_notice');
	
} // end lesson_admin_notices

/**
* Maintaining the acceptable list of meta data field keys for the lesson drip data.
*
* @return array $meta_fields_keys
*/
public function get_meta_field_keys(){
	// create an array of available keys that should be deleted
	$meta_fields_keys = array(
					'_sensei_content_drip_type',
					'_sensei_content_drip_details_date',
					'_sensei_content_drip_details_date_unit_type',
					'_sensei_content_drip_details_date_unit_amount',
					);

	return $meta_fields_keys;

} // end get_meta_field_keys()


/**
 * translates and array of key values into the respective post meta data key values
 *
 * @since 1.0.0
 * @param int $post_id
 * @param array $drip_form_data
 * @return bool $saved
 */
public function save_lesson_drip_data( $post_id , $drip_form_data  ){

	if(empty($post_id) ||  empty( $drip_form_data ) ){
		return false ;
	}

	// remove all existing sensei lesson drip data from the current lesson
	$this->delete_lesson_drip_data( $post_id );

	// save each key respectively
	foreach ($drip_form_data as $key => $value) {
		update_post_meta( $post_id , $key , $value );
	}

	// all done 
	return true;

} // end save_lesson_drip_data

/**
* translates and array of key values into the respective post meta data key values
* 
* @since 1.0.0
* @param string $post_id 
* @return array $lesson_drip_data
*/
public function get_lesson_drip_data( $post_id ){
	
	// exit if and empty post id was sent through
	if( empty( $post_id ) ){
		return false;
	}

	// get an array of available keys that should be deleted
	$meta_fields = $this->get_meta_field_keys();

	// empty array that will store the return values
	$lesson_drip_data = array();

	foreach ($meta_fields as $fieldKey) {
		$value = get_post_meta( $post_id , $fieldKey , true );
		
		// assign the key if a value exists
		if(!empty( $value ) ) {
			$lesson_drip_data[ $fieldKey ] = $value;
		}

	} // end foreach

	return $lesson_drip_data;

} // end get_lesson_drip_data
 

/**
 * cleans out the lessons existing drip meta data to prepare for saving
 *
 * @param int $post_id
 * @since 1.0.0
 * @return void
 */
public function delete_lesson_drip_data( $post_id ){

	if( empty( $post_id ) ){
		return false;
	}

	// create an array of available keys that should be deleted
	$meta_fields = $this->get_meta_field_keys();

	foreach ($meta_fields as $fieldKey) {
		delete_post_meta( $post_id , $fieldKey );
	}

} // delete_lesson_drip_data

/**
 *   The function returns an array of lesson_ids . All those with drip type set to dynamic or absolute
 *
 *	@return array $lessons array containing lesson ids
 */
public static function get_all_dripping_lessons(){

	$lessons =  array();

	// determine the lesson query args
	$lesson_query_args = array(
		'post_type' => 'lesson' ,
		'numberposts' => -1,
		'meta_query'=> array(
			'relation' => 'OR',
			array(
				'key' => '_sensei_content_drip_type',
				'value' => 'absolute' ,
			),
			array(
				'key' => '_sensei_content_drip_type',
				'value' => 'dynamic' ,
			),
		),
	);

	// get the lesson matching the query args
	$wp_lesson_objects = get_posts( $lesson_query_args );

	// create the lessons id array
	if( !empty( $wp_lesson_objects ) ){
		foreach ($wp_lesson_objects as $lesson_object) {
			$lessons[] = $lesson_object->ID;
		}
	}

	return $lessons;
} // get_all_dripping_lessons

} // Scd_ext_lesson_frontend class 