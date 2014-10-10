<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Sensei Content Drip ( scd ) Learner management functionality
 *
 * This class handles all of the functionality for the plugins email functionality.
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
 * - manipulte_drip_type
 * - is_manual_drip_active
 * - manual_drip_interface
 */

class Scd_Ext_Learner_Management{

/**
* constructor
*
*/
public function __construct(){
	add_filter('scd_is_drip_active', array( $this, 'manipulte_drip_type' ), 1 ,2);

	if(is_admin() ){
		// add the interface
		add_action('sensei_learners_extra', array( $this, 'manual_drip_interface' ) );
		// save the data 
		add_action( 'admin_init', array( $this, 'log_manual_drip_activity' ) );
	}
}// end construct


/**
* manual_drip_interface() markup for the manual drip functionality
*
* @return void
*/
public function manual_drip_interface(){
	global $woo_sensei_content_drip;

	$course_id = $_GET['course_id'];
	
	// get al the users taking this course
	$course_users = $woo_sensei_content_drip->utils->get_course_users( $course_id );
	$course_lessons = $woo_sensei_content_drip->lesson_admin->get_course_lessons( $course_id );

?>
	<div class="postbox">
			<h3><span><?php _e( 'Manual Content Drip', 'sensei-content-drip' ); ?></span></h3>
			<div class="inside">
				<form name="scd_manual_drip_learners_lesson" action="" method="post">
					<p>
						<?php _e( 'Use this to give a learner access to any lesson, overriding the content drip schedule.', 'sensei-content-drip' ); ?>
					</p>	
					<p>
						<select name="scd_select_learner" id="scd_select_learner">
							<option value=""><?php _e( 'Select learner', 'sensei-content-drip' ); ?></option>
							<?php 
								// add the users as option
								foreach( $course_users as $user_id ){
									echo '<option value="' . $user_id . '" >';

									// get the users details
									$user = get_user_by('id', $user_id );
									$first_name = $user->first_name ;
									$last_name = $user->last_name;
									$display_name = $user->display_name;

									echo $first_name . ' ' . $last_name . ' ( ' . $display_name . ' ) ';
									echo '</option>';	
								} // end for each
							?>
						</select>
					</p>
					<p>			

						<select name="scd_select_course_lesson" id="scd_select_course_lesson" class=''>
							<option value=""><?php _e( 'Select a Lesson', 'sensei-content-drip' ); ?></option>
							<?php 
								// add the users as option
								foreach( $course_lessons as $lesson ){
									echo '<option value="' . $lesson->ID . '" >';

									// get the lesson title
									echo $lesson->post_title;
									echo '</option>';	
								} // end for each
							?>
						</select>
					</p>
					<p><?php submit_button( __( 'Give Access', 'sensei-content-drip' ), 'primary', 'scd_log_learner_lesson_manual_drip_submit', false, array() ); ?></p>
					<?php echo wp_nonce_field( 'scd_log_learner_lesson_manual_drip', 'scd_learner_lesson_manual_drip' ); ?>
				</form>
			</div>

	<script>
	<!--
	( function( $ ){
		    $('select#scd_select_learner').chosen();
		    $('select#scd_select_course_lesson').chosen();

		    $('#scd_log_learner_lesson_manual_drip_submit').hide();
		    $('#scd_select_course_lesson_chosen').hide();

		    $('select#scd_select_learner').on( 'change', function(e){
		    	 
			    slectedValue = 	$(this).val();

			    if( $.isNumeric( slectedValue ) ){
			    	// show the list of course lesson and enable the button
			    	$('#scd_log_learner_lesson_manual_drip_submit').show();
		    		$('#scd_select_course_lesson_chosen').show();
			    }else{
			    	// hide the list of course lesson and disable the button
			    	$('#scd_log_learner_lesson_manual_drip_submit').hide();
		    		$('#scd_select_course_lesson_chosen').hide();
			    }
		    });

	}( jQuery ) );
	-->
	</script>		
	</div>
<?php




}// end manual_drip_interface

/**
* get the $_POST form data
*
* @return void
*/
public function log_manual_drip_activity(){
	global $woothemes_sensei;

	// verify nonce field exist
	if( ! isset( $_POST['scd_learner_lesson_manual_drip'] ) ) {
		return ;
	}

	// verify the nonce
	if( ! wp_verify_nonce( $_POST['scd_learner_lesson_manual_drip'], 'scd_log_learner_lesson_manual_drip' ) ) {
		// exit
		return;
	}

	// verify incomming fields
	if(  ! isset( $_POST[ 'scd_select_learner' ] )  
		|| empty( $_POST[ 'scd_select_learner' ] )
		|| !isset( $_POST[ 'scd_select_course_lesson' ] ) 
		|| empty( $_POST[ 'scd_select_course_lesson' ] )
		|| !isset( $_POST[ 'scd_log_learner_lesson_manual_drip_submit' ] ) ){
		// exit
		return;
	}

	// get the $_POST values
	$user_id =  $_POST[ 'scd_select_learner' ] ;
	$lesson_id = $_POST[ 'scd_select_course_lesson' ] ;

	// get the users details
	$user = get_user_by('id', $user_id  );

	if( 'WP_User' != get_class( $user ) ){
		// exit as this is not a valid user
		return;
	}
	

    // Create the log argument
    $args = array(
                        'post_id' => $lesson_id,
                        'username' => $user->user_login,
                        'user_email' => $user->user_email,
                        'user_url' => $user->user_url,
                        'data' => 'true',
                        'type' => 'scd_manual_drip', /* FIELD SIZE 20 */
                        'parent' => 0,
                        'user_id' => $user->ID,
                        'action' => 'update'
                        );

    // log the users activity on the lesson drip
    $activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

	add_action( 'admin_notices', array( $this, 'scd_manual_drip_admin_notice' ) );

    return;
}// end log_manual_drip_activity

/**
* show the success on update
*
* @return void
*/
public function scd_manual_drip_admin_notice() {
    ?>
    <div class="updated">
        <p><?php _e( 'Manual Drip Saved', 'sensei-content-drip' ); ?></p>
    </div>
    <?php
}// end scd_manual_drip_admin_notice

/**
* manipulte_drip_type() posibly change the drip active status
*
* @return void
*/
public function manipulte_drip_type( $drip_status ,  $lesson_id ){
 	//	get the current user id
 	$current_user = wp_get_current_user();
 	if( 'WP_User' != get_class( $current_user ) ){
 		return $rip_status;
 	}
	$user_id = $current_user->ID;

	// get the lesson/course sensei activity for drip manual drip
	$args =  array( 'post_id' => intval( $lesson_id ) , 'user_id' => $user_id , 'type' => 'scd_manual_drip' ) ;

	// get the sensei activity, false asks to only return the comment count
	$activity = WooThemes_Sensei_Utils::sensei_check_for_activity( $args ,  false );

	// the acticity is not empty change the drip type
 	if( ! empty( $activity ) && $activity > 0 ){
 		$drip_status = false;
 	}

	return $drip_status;
}// end manipulte_drip_type



}// end class 

	