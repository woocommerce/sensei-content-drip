<?php
/*
 * Plugin Name: Sensei Content Drip
 * Version: 2.0.0
 * Plugin URI: https://woocommerce.com/products/sensei-content-drip/
 * Description:  Control access to Sensei lessons by scheduling them to become available after a determined time.
 * Author: Automattic
 * Author URI: https://automattic.com/
 * Requires at least: 3.9
 * Tested up to: 5.1
 * Requires PHP: 5.6
 * Domain path: /lang/
 * Woo: 543363:8ee2cdf89f55727f57733133ccbbfbb0
 *
 * @package WordPress
 * @author Automattic
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SENSEI_CONTENT_DRIP_VERSION', '2.0.0' );
define( 'SENSEI_CONTENT_DRIP_PLUGIN_FILE', __FILE__ );
define( 'SENSEI_CONTENT_DRIP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/includes/class-scd-ext-dependency-checker.php';

if ( ! Scd_Ext_Dependency_Checker::are_system_dependencies_met() ) {
	add_action( 'admin_init', array( 'Scd_Ext_Dependency_Checker', 'deactivate_self' ) );
	return;
}

require_once dirname( __FILE__ ) . '/includes/class-sensei-content-drip.php';

// Load the plugin after all the other plugins have loaded.
add_action( 'plugins_loaded', array( 'Sensei_Content_Drip', 'init' ), 5 ) ;

