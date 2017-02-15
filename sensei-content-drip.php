<?php
/*
 * Plugin Name: Sensei Content Drip
 * Version: 1.0.8
 * Plugin URI: http://www.woothemes.com/products/sensei-content-drip/
 * Description:  Control access to Sensei lessons by scheduling them to become available after a determined time.
 * Author: WooThemes
 * Author URI: http://www.woothemes.com/
 * Requires at least: 3.9
 * Tested up to: 4.7.2
 * Domain path: /lang/
 *
 * @package WordPress
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once __DIR__ . '/woo-includes/woo-functions.php';
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '8ee2cdf89f55727f57733133ccbbfbb0', '543363' );

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WooThemes_Sensei_Dependencies' ) ) {
	require_once __DIR__ . '/woo-includes/class-woothemes-sensei-dependencies.php';
}

/**
 * Sensei Detection
 */
if ( ! function_exists( 'is_sensei_active' ) ) {
	function is_sensei_active() {
		return WooThemes_Sensei_Dependencies::sensei_active_check();
	}
}


if ( is_sensei_active() ) {

	require_once __DIR__ . '/includes/class-sensei-content-drip.php';

	/**
	 * Returns the main instance of Sensei_Content_Drip to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object Sensei_Content_Drip
	 */
	function Sensei_Content_Drip() {
		return Sensei_Content_Drip::instance( __FILE__, '1.0.8' );
	}

	// load this plugin only after sensei becomes available globaly
	add_action( 'plugins_loaded', 'Sensei_Content_Drip' ) ;

	/**
	* Plugin Activation
	*/
	register_activation_hook( __FILE__, 'sensei_content_drip_activation' );

	function sensei_content_drip_activation() {
		$hook = 'woo_scd_daily_cron_hook';

		if ( false !== wp_next_scheduled( $hook ) ) {
			wp_clear_scheduled_hook( $hook );
		}

		wp_schedule_event( time(), 'daily', $hook );
	}


	/**
	 * Plugin Deactivation
	 */
	register_deactivation_hook( __FILE__, 'sensei_content_drip_deactivation' );

	function sensei_content_drip_deactivation() {
		$hook = 'woo_scd_daily_cron_hook';
		wp_clear_scheduled_hook( $hook );

	}
}
