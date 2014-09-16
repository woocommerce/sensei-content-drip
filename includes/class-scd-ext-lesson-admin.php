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

	// hook int all post of type lesson to determin if they are 
	add_action('add_meta_boxes', array( $this, 'add_leson_content_drip_meta_box' ) );

	// save the meta box
	add_action('save_post', array( $this, 'save_course_drip_meta_box_data' ) );

	// admin_notices
	add_action( 'admin_notices', array( $this, 'lesson_admin_notices' ) , 80 );	

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
	add_meta_box( 'content-drip-lesson', __('Sensei Content Drip','sensei-content-drip') , array( $this, 'content_drip_lesson_meta_content'  ), 'lesson' , 'normal', 'default' , null  );

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

	// setup the forms value variable to be empty , this is to avoid php notices
	$selected_drip_type = '';
	$absolute_date_value = '';
	$selected_dynamic_time_unit_type = '';
	$dynamic_unit_amount = '';
	$dynamic_drip_pre_lesson = '';

	// get the lesson drip meta data
	$lesson_drip_data = $this->get_lesson_drip_data( $post->ID );

	// get the lessons meta data
	$lesson_pre_requisite = get_post_meta( $post->ID , '_lesson_prerequisite', true );
	$current_lesson_course = get_post_meta( $post->ID , '_lesson_course', true );

	//show nothing  if no course is selecteda
	if( empty( $current_lesson_course ) ){
		echo '<p>'. __( 'In oreder to use the content drip settings, please select a course for this lesson.' ) . '</p>';
		// esit without displaying the rest of the settings
		return;
	}

	// get all the lesson for the current lessons course , if no course selected it will return all lessons
	$related_lessons_array =  $this->get_course_lessons( $current_lesson_course , $post->ID );

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
		$dynamic_drip_pre_lesson = absint( $lesson_drip_data['_sensei_content_drip_dynamic_pre_lesson_id'] ) ;

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
			//does this lesson have a  pre-requiste lesson ? 
			$has_pre_requisite = empty( $lesson_pre_requisite ) ? 'false'  : 'true' ; 
		?>
		<option data-has-pre="<?php echo $has_pre_requisite ?> " <?php selected( 'dynamic', $selected_drip_type  ) ?> value="dynamic"  class="dynamic"> <?php _e( 'A specific interval after another lesson', 'sensei-content-drip' ); ?> </option>
	</select></p>
	
	<p><div class="dripTypeOptions absolute <?php echo $absolute_hidden_class;?> ">
		<p><span class='description'><?php _e('Select the date on which this lesson should become available ?', 'sensei-content-drip'); ?></span></p>
		<input type="date" id="datepicker" name="absolute[datepicker]" value="<?php echo $absolute_date_value  ;?>" class="absolute-datepicker" />
	</div></p>
	<p> 
		<div class="dripTypeOptions dynamic <?php echo $dymaic_hidden_class;?> ">

			
		<?php if( empty( $current_lesson_course ) ){ ?>
			<p>
				<?php _e( 'Please select a course for this lesson in order to use this drip type.', 'sensei-content-drip'  ); ?>
			</p>

		<?php }elseif( count( $related_lessons_array )  < 1 ){ ?>

			<p>
				<?php _e( 'The course does not contain any other lessons. Please add another lesson for this drip type to become available', 'sensei-content-drip'  ); ?>
			</p>

		<?php }else{  ?>

			<div id="dynamic-dripping-1" class='dynamic-dripping'>
				<input type='number' name='dynamic-unit-amount[1]' class='unit-amount' value="<?php echo $dynamic_unit_amount; ?>" ></input>
		
				<select name='dynamic-time-unit-type[1]' class="dynamic-time-unit">
					<option <?php selected( 'day', $selected_dynamic_time_unit_type );?> value="day"> <?php _e('Day(s)', 'sensei-content-drip'); ?></option>
					<option <?php selected( 'week', $selected_dynamic_time_unit_type );?>  value="week"> <?php _e('Week(s)', 'sensei-content-drip'); ?> </option>
					<option <?php selected( 'month', $selected_dynamic_time_unit_type );?>  value="month"> <?php _e('Month(s)', 'sensei-content-drip'); ?>  </option>
				</select>
			</div>	

			<span> &nbsp; &nbsp;<?php _e('After','sensei-content-drip'); ?>&nbsp; &nbsp;</span>

			<select id="dynamic-drip-related-lessons" name="dynamic_drip_pre_lesson" class="chosen_select widefat">
				<option value=""> <?php _e( 'None', 'sensei-content-drip' );?> </option>
				
			<?php foreach( $related_lessons_array as $lesson ) { ?>
				<option value="<?php echo esc_attr( $lesson->ID ); ?>"  
					<?php selected( $lesson->ID, $dynamic_drip_pre_lesson, true ); ?> > 
						<?php echo esc_html( $lesson->post_title );?>
				</option>
			<?php } // End For Loop ?>

			</select>

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
 * @return array WP_Post 
 */
public function get_course_lessons( $course_id = 0, $exclude = '' ) {

	$lessons = array();

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
		$pre_lesson_id	= $_POST['dynamic_drip_pre_lesson'];

		// input validation
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
					'_sensei_content_drip_dynamic_pre_lesson_id' => $pre_lesson_id ,
				);
	}

	// update the meta data
	$this->save_lesson_drip_data( $post_id , $new_data  );

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
			$message =  $message . ' The content drip type was reset to "none".';
			echo '<div class="'. $type .' fade"><p>Sensei Content Drip '. $type .': ' . $message . '</p></div>';
	}

	// clear all notices
	delete_option('_sensei_content_drip_lesson_notice');
	
} // end lesson_admin_notices

/**
* Maintianing the acceptable list of meta data field keys for the lesson drip data.
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
					'_sensei_content_drip_dynamic_pre_lesson_id',
					);

	return $meta_fields_keys;

} // end get_meta_field_keys()


/**
* translates and array of key values into the respective post meta data key values
* 
* @since 1.0.0
* @param array $keys_values  
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
* @return array $drip_data
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
* cleans out the lessons sensei content drip meta data to prepare for saving
* 
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

} // Scd_ext_lesson_frontend class 