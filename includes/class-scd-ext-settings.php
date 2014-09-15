<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Sensei Content Drip ( scd ) Email Settings class
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
 * - register_settings_tab
 */
class Scd_Ext_settings {

public function __construct(){
	if( is_admin() ){
		add_filter( 'sensei_settings_tabs', array( $this, 'register_settings_tab' ) );
		add_filter( 'sensei_settings_fields', array( $this, 'register_settings_fields' ) );
	}
}// end __construct

/**
* sensei get_setting value wrapper
* 
* @return string $settings value
*/
public function get_setting( $setting_token ){
	global $woothemes_sensei;

	// get all settings from sensei
	$settings = $woothemes_sensei->settings->get_settings();

	if( empty( $settings )  || ! isset(  $settings[ $setting_token ]  ) ){
		return '';
	}

	return $settings[ $setting_token ];
}

/**
* Attaches the the contend drip settings to the sensei admin settings tabs
* 
* @param array $sensei_settings_tabs;
* @return array  $sensei_settings_tabs
*/
public function register_settings_tab( $sensei_settings_tabs ){

	$scd_tab  = array(
						'name' 			=> __( 'Content Drip', 'sensei-content-drip' ),
						'description'	=> __( 'Optional settings for the Contentd Drip extension', 'sensei-content-drip' )
				);

	$sensei_settings_tabs['sensei-content-drip-settings'] = $scd_tab;

	return $sensei_settings_tabs;

}// end register_settings_tab


/**
* Includes the content drip settings fields 
* 
* @param array $sensei_settings_fields;
* @return array  $sensei_settings_fields
*/
public function register_settings_fields( $sensei_settings_fields ){

	$sensei_settings_fields['scd_drip_message'] = array(
									'name' => __( 'Drip Message', 'sensei-content-drip' ),
									'description' => __( 'The user will see this when the content is not yet available. The [date] shortcode will be replaced by the actual date' ),
									'type' => 'textarea',
									'default' => 'This lesson will become available on [date]',
									'section' => 'sensei-content-drip-settings'
									);

	return $sensei_settings_fields;

}// end register_settings_tab

}// end Scd_Ext_settings