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
	add_filter('scd_is_drip_active', array( $this, 'manipulte_drip_type' ), 1 , 2 );

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

	echo "Manual drip interface";

}// end manual_drip_interface


/**
* hook into the admin init and get the post form data
*
* @return void
*/

public function log_manual_drip_activity(){


}// end log_manual_drip_activity

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

	// the acticity is not empty change the drip type
 	if( false ){

 	}

	return $drip_status;
}// end manipulte_drip_type



}// end class 

	