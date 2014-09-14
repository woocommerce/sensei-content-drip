<?php
/*
 * Plugin Name: Sensei Content Drip
 * Version: 1.0.0
 * Plugin URI: http://www.woothemes.com/
 * Description:  I will allow you to release sensei lesson content at a determined timee so you can control when students have access to the content.
 * Author: WooThemes
 * Author URI: http://www.woothemes.com/
 * Requires at least: 3.9
 * Tested up to: 3.9.1
 *
 * @package WordPress
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'product_key', 'product_id' );

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WooThemes_Sensei_Dependencies' ) ) {
	require_once 'woo-includes/class-woothemes-sensei-dependencies.php';
}

/**
 * Sensei Detection
 */
if ( ! function_exists( 'is_sensei_active' ) ) {
  function is_sensei_active() {
    return WooThemes_Sensei_Dependencies::sensei_active_check();
  }
}


if( is_sensei_active() ) {

	require_once( 'includes/class-sensei-content-drip.php' );

	/**
	 * Returns the main instance of Sensei_Content_Drip to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object Sensei_Content_Drip
	 */
	function Sensei_Content_Drip() {
		return Sensei_Content_Drip::instance( __FILE__, '1.0.0' );
	}

	Sensei_Content_Drip();
	
	/**
	* Plugin Activation
	*/
	register_activation_hook( __FILE__, 'sensei_content_drip_activation' );

	function sensei_content_drip_activation(){
		wp_schedule_event( time(), 'daily', 'woo_scd_daily_cron_hook' );
	}// end sensei_content_drip_activation


	/**
	 * Plugin Deactivation
	 */
	register_deactivation_hook( __FILE__, 'sensei_content_drip_deactivation' );

	function sensei_content_drip_deactivation() {
		
		$hook = 'woo_scd_daily_cron_hook';
		// get all system crons
	    $crons = _get_cron_array();
	    if ( empty( $crons ) ) {
	        return;
	    }

	    // loop through all of theme and remove this plugin's cron 
	    foreach( $crons as $timestamp => $cron ) {
	        if ( ! empty( $cron[$hook] ) )  {
	            unset( $crons[$timestamp][$hook] );
	        }
	    }
	    _set_cron_array( $crons );
	    
	} // end sensei_content_drip_deactivation

} // end if is_sensei_active()
