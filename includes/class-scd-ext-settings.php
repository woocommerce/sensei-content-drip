<?php  
//security first
if ( ! defined( 'ABSPATH' ) ) exit;
/**
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
 * - __construct
 * - get_setting
 * - register_settings_tab
 * - register_settings_fields
 * todo go through all functions to make sure theyr doc info is correct
 */
class Scd_Ext_Settings {
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
                            'description'	=> __( 'Optional settings for the Content Drip extension', 'sensei-content-drip' )
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
                                        'default' => 'This lesson will only become available on [date].',
                                        'section' => 'sensei-content-drip-settings'
                                        );

        $sensei_settings_fields['scd_drip_quiz_message'] = array(
            'name' => __( 'Quiz Drip Message', 'sensei-content-drip' ),
            'description' => __( 'The user will see this on the lesson quiz when the lesson is not yet available. The [date] shortcode will be replaced by the actual date' ),
            'type' => 'textarea',
            'default' => 'This quiz will only become available on [date].',
            'section' => 'sensei-content-drip-settings'
        );



        // Email related settings
        $sensei_settings_fields['scd_email_body_notice_html'] = array(
                                    'name' => __( 'Email Before Lessons', 'sensei-content-drip' ),
                                    'description' => __( 'The text before the list of lessons dripping today.' ),
                                    'type' => 'textarea',
                                    'default' => 'The following lessons will become available today:',
                                    'section' => 'sensei-content-drip-settings'
                                    );

        $sensei_settings_fields['scd_email_footer_html'] = array(
                                    'name' => __( 'Email Footer', 'sensei-content-drip' ),
                                    'description' => __( 'The text below the list of lessons dripping today' ),
                                    'type' => 'textarea',
                                    'default' => 'Visit the online course today to start taking the lessons: [home_url]',
                                    'section' => 'sensei-content-drip-settings'
                                    );

        return $sensei_settings_fields;

    }// end register_settings_tab
}// end Scd_Ext_settings